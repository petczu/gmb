<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Ai\Agents\WorkspaceAnalyst;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Throwable;

/**
 * Floating "Ask AI" chat (round launcher bottom-right, Intercom style) over the
 * current workspace's review data. Read-only agent; history survives page
 * navigation within the session.
 */
class AskAiChat extends Component
{
    /** @var list<array{role: string, content: string}> */
    public array $messages = [];

    public string $question = '';

    public bool $busy = false;

    public function mount(): void
    {
        $this->messages = (array) session($this->sessionKey(), []);
    }

    /** Two-phase send: render the user's bubble + spinner, then answer. */
    public function send(): void
    {
        $question = trim($this->question);

        if ($question === '' || $this->busy) {
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

    public function clearChat(): void
    {
        $this->messages = [];
        $this->busy = false;
        $this->persist();
    }

    public function render(): View
    {
        return view('livewire.ask-ai-chat');
    }

    protected function persist(): void
    {
        session([$this->sessionKey() => $this->messages]);
    }

    protected function sessionKey(): string
    {
        return 'ask_ai_chat_'.(string) session('current_workspace_id');
    }
}
