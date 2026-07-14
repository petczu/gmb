<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Competitors\DataForSeoClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * DataForSEO snapshot client: basic-auth live lookup by place_id, normalized
 * to the same shape as PlacesClient::details(), with task errors surfaced.
 */
class DataForSeoClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.dataforseo.login', 'login@example.com');
        config()->set('services.dataforseo.password', 'secret-api-password');
    }

    public function test_details_queries_by_place_id_and_normalizes(): void
    {
        Http::fake([
            'api.dataforseo.com/v3/business_data/google/my_business_info/live' => Http::response([
                'tasks' => [[
                    'status_code' => 20000,
                    'result' => [[
                        'items' => [[
                            'place_id' => 'ChIJ-test',
                            'title' => 'Rival Escape',
                            'address' => 'Main St 5, Wien',
                            'rating' => ['value' => 4.6, 'votes_count' => 321, 'rating_type' => 'Max5'],
                        ]],
                    ]],
                ]],
            ]),
        ]);

        $details = app(DataForSeoClient::class)->details('ChIJ-test');

        Http::assertSent(function ($request): bool {
            $payload = $request->data()[0] ?? [];

            return $request->hasHeader('Authorization')
                && $payload['keyword'] === 'place_id:ChIJ-test'
                && filled($payload['location_code'])
                && filled($payload['language_code']);
        });

        $this->assertSame([
            'place_id' => 'ChIJ-test',
            'name' => 'Rival Escape',
            'address' => 'Main St 5, Wien',
            'rating' => 4.6,
            'reviews_count' => 321,
        ], $details);
    }

    public function test_task_level_errors_become_exceptions(): void
    {
        Http::fake([
            'api.dataforseo.com/*' => Http::response([
                'tasks' => [[
                    'status_code' => 40501,
                    'status_message' => 'Invalid Field: keyword',
                ]],
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('40501');

        app(DataForSeoClient::class)->details('ChIJ-broken');
    }

    public function test_configured_requires_both_credentials(): void
    {
        $this->assertTrue(app(DataForSeoClient::class)->configured());

        config()->set('services.dataforseo.password', null);
        $this->assertFalse(app(DataForSeoClient::class)->configured());
    }
}
