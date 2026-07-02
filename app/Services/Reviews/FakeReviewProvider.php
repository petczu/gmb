<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Services\Reviews\Data\LocationData;
use App\Services\Reviews\Data\ReviewData;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Deterministic in-memory provider for development and tests. Produces stable
 * sample locations + reviews per account so the whole P1 vertical (sync, UI,
 * reply) works before live Zernio credentials exist. Swap for ZernioProvider
 * by setting REVIEWS_DRIVER=zernio.
 */
class FakeReviewProvider implements ReviewProvider
{
    public function listLocations(string $accountId): array
    {
        return [
            new LocationData(
                externalId: 'loc-downtown',
                name: 'Acme Coffee — Downtown',
                address: '12 Market St, Springfield',
                placeId: 'ChIJ-fake-downtown',
                phone: '+1 555 0100',
                websiteUrl: 'https://acme.example/downtown',
                rating: 4.3,
                reviewsCount: 4,
                isVerified: true,
            ),
            new LocationData(
                externalId: 'loc-harbor',
                name: 'Acme Coffee — Harbor',
                address: '88 Pier Ave, Springfield',
                placeId: 'ChIJ-fake-harbor',
                phone: '+1 555 0123',
                websiteUrl: 'https://acme.example/harbor',
                rating: 3.6,
                reviewsCount: 3,
                isVerified: true,
            ),
        ];
    }

    public function listReviews(string $accountId, ?string $locationExternalId = null, ?CarbonInterface $since = null): array
    {
        $all = [
            $this->review('rev-d1', 'loc-downtown', 5, 'Maya R.', 'Best flat white in town, friendly baristas.', 2, 'Thank you Maya — see you soon!', 1),
            $this->review('rev-d2', 'loc-downtown', 4, 'Tom B.', 'Great coffee, a bit slow at peak hours.', 6),
            $this->review('rev-d3', 'loc-downtown', 2, 'Critic77', 'Order was wrong twice and no apology.', 9),
            $this->review('rev-d4', 'loc-downtown', 5, 'Lena K.', 'Cozy spot, excellent pastries.', 14),
            $this->review('rev-h1', 'loc-harbor', 5, 'Sam P.', 'Lovely harbor view and great espresso.', 3),
            $this->review('rev-h2', 'loc-harbor', 1, 'Dana W.', 'Rude staff, waited 20 minutes. Never again.', 5),
            $this->review('rev-h3', 'loc-harbor', 4, 'Iggy', 'Solid cortado, will return.', 11),
        ];

        return array_values(array_filter($all, function (ReviewData $r) use ($locationExternalId, $since) {
            if ($locationExternalId !== null && $r->locationExternalId !== $locationExternalId) {
                return false;
            }
            if ($since !== null && $r->createdAtExternal !== null && $r->createdAtExternal->lessThan($since)) {
                return false;
            }

            return true;
        }));
    }

    public function reply(string $accountId, string $reviewExternalId, string $comment): void
    {
        // No-op: the Fake backend always "accepts" the reply. The local Review
        // row is updated by the caller after this returns.
    }

    public function deleteReply(string $accountId, string $reviewExternalId): void
    {
        // No-op: the Fake backend always "accepts" the deletion.
    }

    private function review(string $id, string $loc, int $rating, string $author, string $text, int $daysAgo, ?string $reply = null, ?int $replyDaysAgo = null): ReviewData
    {
        return new ReviewData(
            externalId: $id,
            locationExternalId: $loc,
            rating: $rating,
            authorName: $author,
            text: $text,
            reviewLink: 'https://maps.example/review/'.$id,
            createdAtExternal: Carbon::now()->subDays($daysAgo)->startOfHour(),
            replyText: $reply,
            repliedAt: $reply !== null ? Carbon::now()->subDays($replyDaysAgo ?? max(0, $daysAgo - 1)) : null,
        );
    }
}
