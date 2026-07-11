<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AskAiChat;
use App\Models\AiConversation;
use App\Models\Location;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ask-AI conversations: a chat is saved on the first turn, appears in history,
 * can be reopened, started fresh and deleted (all scoped to the user).
 */
class AskAiConversationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The chat's send() is gated on having a connected location.
        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        Location::create(['name' => 'HQ']);

        // AskAiChat calls auth()->id(); a null id is fine for the query scope.
        Schema::create('ai_conversations', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->json('messages')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('locations');
        parent::tearDown();
    }

    public function test_a_conversation_is_saved_and_titled_from_the_first_message(): void
    {
        Livewire::test(AskAiChat::class)
            ->set('question', 'How many 5-star reviews this month?')
            ->call('send'); // pushes the message and persists before dispatching answer

        $conversation = AiConversation::query()->first();

        $this->assertNotNull($conversation);
        $this->assertSame('How many 5-star reviews this month?', $conversation->title);
        $this->assertNotNull($conversation->last_message_at);
    }

    public function test_new_chat_clears_and_open_reloads(): void
    {
        $saved = AiConversation::create([
            'user_id' => null,
            'title' => 'Old chat',
            'messages' => [['role' => 'user', 'content' => 'hi'], ['role' => 'assistant', 'content' => 'hello']],
            'last_message_at' => now(),
        ]);

        $component = Livewire::test(AskAiChat::class)
            ->call('newChat')
            ->assertSet('messages', [])
            ->assertSet('conversationId', null);

        $component->call('openConversation', $saved->id)
            ->assertSet('conversationId', $saved->id)
            ->assertCount('messages', 2);
    }

    public function test_delete_removes_the_conversation(): void
    {
        $saved = AiConversation::create([
            'user_id' => null, 'title' => 'Trash me',
            'messages' => [['role' => 'user', 'content' => 'x']], 'last_message_at' => now(),
        ]);

        Livewire::test(AskAiChat::class)->call('deleteConversation', $saved->id);

        $this->assertDatabaseMissing('ai_conversations', ['id' => $saved->id]);
    }
}
