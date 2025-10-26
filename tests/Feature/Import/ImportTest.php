<?php

namespace Tests\Feature\Import;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /**
         * TEST ENV SAFETY NETS
         *
         * Ideally, put these in .env.testing:
         *   API_KEY=test-api-key
         *   ALLOWED_IMPORT_SOURCES=nexo,DummySource123
         *
         * If they’re missing, we set safe fallbacks here so the tests are deterministic.
         */
        if (! env('API_KEY')) {
            // Make sure both the middleware (env) and the test (header) see the same key
            putenv('API_KEY=test-api-key');
            $_ENV['API_KEY'] = 'test-api-key';
            $_SERVER['API_KEY'] = 'test-api-key';
        }

        // If the config wasn’t hydrated from .env.testing, force a known list for the tests
        if (empty(config('sources.allowed_sources'))) {
            config(['sources.allowed_sources' => ['nexo', 'DummySource123']]);
        }
    }

    private function validPayload(string $sourceId = 'nexo'): array
    {
        return [
            'source_id' => $sourceId,
            'payload' => [
                'name' => 'Alice Example',
                'email' => 'alice@example.com',
                'assoc_id' => 123,
                'association_name' => 'Example Association',
                'counselor' => 'Jane Counselor',
                'email_counselor' => 'jane.counselor@example.com',
            ],
        ];
    }

    #[Test]
    public function accepts_single_rows_object_from_nexo_and_redirects(): void
    {
        $apiKey = env('API_KEY');

        $response = $this->withHeaders(['X-API-KEY' => $apiKey])
            ->postJson('/api/nexo-import', $this->validPayload('nexo'));

        $response->assertStatus(302);
        $response->assertRedirect(); // optionally: ->assertRedirect(route('events.create'))
    }

    #[Test]
    public function accepts_single_rows_object_from_dummy_source_and_redirects(): void
    {
        $apiKey = env('API_KEY');

        $response = $this->withHeaders(['X-API-KEY' => $apiKey])
            ->postJson('/api/nexo-import', $this->validPayload('DummySource123'));

        $response->assertStatus(302);
        $response->assertRedirect();
    }

    #[Test]
    public function rejects_unregistered_source_id_with_validation_error(): void
    {
        $apiKey = env('API_KEY');

        $payload = $this->validPayload('NotAllowedSource');

        $response = $this->withHeaders(['X-API-KEY' => $apiKey])
            ->postJson('/api/nexo-import', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source_id']);
    }

    #[Test]
    public function validation_fails_for_missing_required_fields(): void
    {
        $apiKey = env('API_KEY');

        // missing rows.name and rows.email
        $payload = [
            'source_id' => 'nexo',
            'payload' => [
                'assoc_id' => 123,
                'association_name' => 'Example Association',
            ],
        ];

        $response = $this->withHeaders(['X-API-KEY' => $apiKey])
            ->postJson('/api/nexo-import', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payload.name', 'payload.email']);
    }
}
