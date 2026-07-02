<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $businessName,
        public string $periodLabel,
        public string $summary,
        public string $pdfPath,
        public string $pdfName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Performance report: {$this->businessName} ({$this->periodLabel})",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.report');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->pdfName)
                ->withMime('application/pdf'),
        ];
    }
}
