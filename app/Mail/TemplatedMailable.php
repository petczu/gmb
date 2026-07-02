<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Templates\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Base for emails whose subject + body come from an editable, super-admin-managed
 * template (see EmailTemplateRenderer). Concrete mailables keep their typed
 * constructors and only map their data to the template's placeholders, so call
 * sites are unchanged. Each concrete mailable declares a `public string $lang`.
 */
abstract class TemplatedMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** Catalogue key of the template to render. */
    abstract protected function templateKey(): string;

    /**
     * Scalar placeholder values, including `url` for the CTA button.
     *
     * @return array<string, string|int|null>
     */
    abstract protected function templateData(): array;

    /**
     * Pre-rendered HTML for {{ block }} tokens (e.g. a reviews table).
     *
     * @return array<string, string>
     */
    protected function blocks(): array
    {
        return [];
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: app(EmailTemplateRenderer::class)->subject($this->templateKey(), $this->lang, $this->templateData()),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: app(EmailTemplateRenderer::class)->render($this->templateKey(), $this->lang, $this->templateData(), $this->blocks()),
        );
    }
}
