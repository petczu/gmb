<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Ai\Agents\WorkspaceAnalyst;
use App\Models\AiConversation;
use App\Models\Location;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;
use Throwable;

/**
 * Floating "Ask AI" chat over the current workspace's review data. Read-only
 * agent. Conversations are saved per user in the tenant DB, so a user can start
 * a new chat or continue an earlier one across sessions and devices.
 */
class AskAiChat extends Component
{
    /** @var list<array{role: string, content: string}> */
    public array $messages = [];

    public ?int $conversationId = null;

    public string $question = '';

    public bool $busy = false;

    public bool $showHistory = false;

    public function mount(): void
    {
        // Resume the user's most recent conversation, if any.
        $latest = $this->query()->latest('last_message_at')->first();

        if ($latest !== null) {
            $this->conversationId = (int) $latest->id;
            $this->messages = (array) $latest->messages;
        }
    }

    /** Whether the workspace has a connected location to ask about. */
    public function hasLocations(): bool
    {
        try {
            return Location::query()->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /** Two-phase send: render the user's bubble + spinner, then answer. */
    public function send(): void
    {
        $question = trim($this->question);

        if ($question === '' || $this->busy || ! $this->hasLocations()) {
            return;
        }

        $key = 'ask-ai:'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, maxAttempts: 30)) {
            $this->messages[] = ['role' => 'assistant', 'content' => __('pages/ask_ai.rate_limited')];
            $this->persist();

            return;
        }
        RateLimiter::hit($key, 3600);

        $this->messages[] = ['role' => 'user', 'content' => $question];
        $this->question = '';
        $this->busy = true;
        $this->showHistory = false;
        $this->persist();

        $this->dispatch('ask-ai-answer');
        $this->dispatch('ask-ai-scroll');
    }

    public function answer(): void
    {
        if (! $this->busy) {
            return;
        }

        $history = array_slice($this->messages, 0, -1);
        $history = array_slice($history, -12); // keep context small
        $question = $this->messages[array_key_last($this->messages)]['content'];

        try {
            $model = (string) config('services.ai.model', 'claude-opus-4-8');
            $response = (new WorkspaceAnalyst($history))->prompt($question, model: $model);

            $this->messages[] = ['role' => 'assistant', 'content' => (string) $response->text];

            if ($workspace = Workspace::find(session('current_workspace_id'))) {
                app(AiCreditService::class)->logUsage(
                    workspace: $workspace,
                    reason: 'ask_ai',
                    model: $model,
                    inputTokens: (int) ($response->usage->promptTokens ?? 0),
                    outputTokens: (int) ($response->usage->completionTokens ?? 0),
                );
            }
        } catch (Throwable $e) {
            Log::warning('Ask AI failed', ['error' => $e->getMessage()]);
            $this->messages[] = ['role' => 'assistant', 'content' => __('pages/ask_ai.failed')];
        } finally {
            $this->busy = false;
            $this->persist();
            $this->dispatch('ask-ai-scroll');
        }
    }

    public function ask(string $question): void
    {
        $this->question = $question;
        $this->send();
    }

    /** Start a fresh conversation (the current one is already saved). */
    public function newChat(): void
    {
        $this->conversationId = null;
        $this->messages = [];
        $this->busy = false;
        $this->showHistory = false;
    }

    /** Load a saved conversation. */
    public function openConversation(int $id): void
    {
        $conversation = $this->query()->whereKey($id)->first();
        if ($conversation === null) {
            return;
        }

        $this->conversationId = (int) $conversation->id;
        $this->messages = (array) $conversation->messages;
        $this->busy = false;
        $this->showHistory = false;
        $this->dispatch('ask-ai-scroll');
    }

    public function deleteConversation(int $id): void
    {
        $this->query()->whereKey($id)->delete();

        if ($this->conversationId === $id) {
            $this->newChat();
        }
    }

    public function toggleHistory(): void
    {
        $this->showHistory = ! $this->showHistory;
    }

    public function render(): View
    {
        return view('livewire.ask-ai-chat', [
            'conversations' => $this->query()
                ->whereNotNull('last_message_at')
                ->latest('last_message_at')
                ->limit(20)
                ->get(['id', 'title', 'last_message_at']),
        ]);
    }

    /** Persist the current thread, creating the conversation on the first turn. */
    protected function persist(): void
    {
        if ($this->messages === []) {
            return;
        }

        $conversation = $this->conversationId !== null
            ? $this->query()->find($this->conversationId)
            : null;

        $conversation ??= new AiConversation(['user_id' => auth()->id()]);

        if (blank($conversation->title)) {
            $firstUser = collect($this->messages)->firstWhere('role', 'user');
            $conversation->title = Str::limit(trim((string) ($firstUser['content'] ?? __('pages/ask_ai.untitled'))), 60);
        }

        $conversation->messages = $this->messages;
        $conversation->last_message_at = now();
        $conversation->user_id ??= auth()->id();
        $conversation->save();

        $this->conversationId = (int) $conversation->id;
    }

    /** Conversations belonging to the current user. */
    protected function query()
    {
        return AiConversation::query()->where('user_id', auth()->id());
    }
}
