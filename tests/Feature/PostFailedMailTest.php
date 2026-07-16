<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PostFailedMail;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The "post failed to publish" email folds the failure reason into the detail
 * line when one is available, else a generic retry hint. (Full HTML rendering
 * of every template is covered by the email-template catalog tests.)
 */
class PostFailedMailTest extends TestCase
{
    /** @return array<string, mixed> */
    private function templateData(PostFailedMail $mail): array
    {
        $method = new ReflectionMethod($mail, 'templateData');

        return $method->invoke($mail);
    }

    public function test_a_reason_is_folded_into_the_detail_line(): void
    {
        $data = $this->templateData(new PostFailedMail(
            name: 'Peter',
            businessName: 'GAME OVER Vienna',
            postsUrl: 'https://app.repunio.com/posts',
            reason: 'Zernio 422: media rejected',
        ));

        $this->assertSame('Peter', $data['name']);
        $this->assertSame('GAME OVER Vienna', $data['business']);
        $this->assertStringContainsString('media rejected', $data['detail']);
    }

    public function test_without_a_reason_it_uses_the_generic_detail(): void
    {
        $data = $this->templateData(new PostFailedMail(
            name: 'Peter',
            businessName: 'GAME OVER Vienna',
            postsUrl: 'https://app.repunio.com/posts',
            reason: null,
        ));

        $this->assertSame(__('emails.post_failed.detail'), $data['detail']);
    }
}
