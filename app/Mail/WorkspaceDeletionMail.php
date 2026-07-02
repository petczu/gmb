<?php

declare(strict_types=1);

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkspaceDeletionMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  string  $stage  'scheduled' (deletion requested) | 'completed' (purged)
     */
    public function __construct(
        public string $workspaceName,
        public string $stage,
        public ?CarbonInterface $purgeAt = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->stage === 'completed'
                ? "Your workspace \"{$this->workspaceName}\" has been deleted"
                : "Your workspace \"{$this->workspaceName}\" is scheduled for deletion",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.workspace-deletion',
            with: [
                'workspaceName' => $this->workspaceName,
                'stage' => $this->stage,
                'purgeAt' => $this->purgeAt,
            ],
        );
    }
}
