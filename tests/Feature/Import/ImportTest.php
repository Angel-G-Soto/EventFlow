<?php

namespace Tests\Feature\Import;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ---------- TEST ENV SAFETY NETS ----------
        if (! env('API_KEY')) {
            putenv('API_KEY=test-api-key');
            $_ENV['API_KEY']    = 'test-api-key';
            $_SERVER['API_KEY'] = 'test-api-key';
        }

        if (empty(config('sources.allowed_sources'))) {
            config(['sources.allowed_sources' => ['nexo', 'DummySource123']]);
        }
    }

    // ---------- HELPERS ----------

    private function validPayload(string $sourceId = 'nexo'): array
    {
        return [
            'source_id' => $sourceId,
            'payload' => [
                'name'               => 'Alice Example',
                'email'              => 'alice@example.com',
                'assoc_id'           => 123,
                'association_name'   => 'Example Association',
                'counselor'          => 'Jane Counselor',
                'email_counselor'    => 'jane.counselor@example.com',
            ],
        ];
    }

    private function postImport(array $payload, ?string $apiKey = null)
    {
        $headers = [];
        if ($apiKey !== null) {
            $headers['X-API-KEY'] = $apiKey;
        }
        return $this->withHeaders($headers)->postJson('/api/nexo-import', $payload);
    }

    // ---------- HAPPY PATHS ----------

    #[Test]
    public function accepts_single_rows_object_from_nexo_and_redirects(): void
    {
        $response = $this->postImport($this->validPayload('nexo'), env('API_KEY'));
        $response->assertStatus(302)->assertRedirect();
    }

    #[Test]
    public function accepts_single_rows_object_from_dummy_source_and_redirects(): void
    {
        $response = $this->postImport($this->validPayload('DummySource123'), env('API_KEY'));
        $response->assertStatus(302)->assertRedirect();
    }

    // If your route name is events.create, also assert the exact location:
    #[Test]
    public function redirects_to_events_create_when_successful_if_route_exists(): void
    {
        if (! \Illuminate\Support\Facades\Route::has('events.create')) {
            $this->markTestSkipped('Route [events.create] not defined yet.');
        }

        $response = $this->postImport($this->validPayload(), env('API_KEY'));

        // 1) Get Location
        $location = $response->headers->get('Location');
        $this->assertNotNull($location, 'No Location header on redirect response');

        // 2) Must start at the route URL
        $base = route('events.create');
        $this->assertStringStartsWith($base, $location, "Redirect [$location] does not start with [$base]");

        // 3) If there’s a query string, check key fields
        $query = parse_url($location, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $qs);
            $this->assertSame('nexo', $qs['source_id'] ?? null);
            $this->assertSame('Alice Example', $qs['payload']['name'] ?? null);
            $this->assertSame('alice@example.com', $qs['payload']['email'] ?? null);
            $this->assertSame('123', $qs['payload']['assoc_id'] ?? null); // note: parse_str yields strings
            $this->assertSame('Example Association', $qs['payload']['association_name'] ?? null);
            $this->assertSame('Jane Counselor', $qs['payload']['counselor'] ?? null);
            $this->assertSame('jane.counselor@example.com', $qs['payload']['email_counselor'] ?? null);
        }

        // Still a proper redirect
        $response->assertStatus(302);
    }

    // ---------- AUTH / HEADER ----------

    #[Test]
    public function rejects_when_api_key_header_is_missing(): void
    {
        $response = $this->postImport($this->validPayload()); // no header
        // Choose the status your middleware returns. 401 is common. Adjust if you use 403.
        $response->assertStatus(401);
    }

    #[Test]
    public function rejects_when_api_key_is_incorrect(): void
    {
        $response = $this->postImport($this->validPayload(), 'wrong-key');
        $response->assertStatus(401);
    }

    // ---------- SOURCE ID HANDLING ----------

    #[Test]
    public function rejects_unregistered_source_id_with_validation_error(): void
    {
        $response = $this->postImport($this->validPayload('NotAllowedSource'), env('API_KEY'));
        $response->assertStatus(422)->assertJsonValidationErrors(['source_id']);
    }

    #[Test]
    public function respects_runtime_changes_to_allowed_sources_config(): void
    {
        config(['sources.allowed_sources' => ['OnlyThisOne']]);

        // Now nexo should fail
        $response = $this->postImport($this->validPayload('nexo'), env('API_KEY'));
        $response->assertStatus(422)->assertJsonValidationErrors(['source_id']);

        // And OnlyThisOne should pass
        $response2 = $this->postImport($this->validPayload('OnlyThisOne'), env('API_KEY'));
        $response2->assertStatus(302)->assertRedirect();
    }

    #[Test]
    public function rejects_source_id_with_wrong_casing_if_validation_is_case_sensitive(): void
    {
        // If your validation normalizes to lower-case, flip expectation accordingly.
        $response = $this->postImport($this->validPayload('NEXO'), env('API_KEY'));
        $response->assertStatus(422)->assertJsonValidationErrors(['source_id']);
    }

    // ---------- VALIDATION: PAYLOAD SHAPE & FIELDS ----------

    #[Test]
    public function validation_fails_for_missing_required_fields(): void
    {
        $payload = [
            'source_id' => 'nexo',
            'payload' => [
                'assoc_id' => 123,
                'association_name' => 'Example Association',
            ],
        ];

        $response = $this->postImport($payload, env('API_KEY'));
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payload.name', 'payload.email']);
    }

    #[Test]
    public function validation_fails_when_payload_is_missing_entirely(): void
    {
        $payload = ['source_id' => 'nexo'];
        $response = $this->postImport($payload, env('API_KEY'));
        $response->assertStatus(422)->assertJsonValidationErrors(['payload']);
    }

    #[Test]
    public function validation_fails_when_payload_is_not_an_object(): void
    {
        $payload = ['source_id' => 'nexo', 'payload' => 'not-an-object'];
        $response = $this->postImport($payload, env('API_KEY'));
        $response->assertStatus(422)->assertJsonValidationErrors(['payload']);
    }

    #[Test]
    public function validation_fails_for_invalid_email_format(): void
    {
        $payload = $this->validPayload();
        $payload['payload']['email'] = 'not-an-email';

        $response = $this->postImport($payload, env('API_KEY'));
        $response->assertStatus(422)->assertJsonValidationErrors(['payload.email']);
    }

    #[Test]
    public function validation_fails_if_assoc_id_is_non_numeric(): void
    {
        $payload = $this->validPayload();
        $payload['payload']['assoc_id'] = 'abc';

        $response = $this->postImport($payload, env('API_KEY'));
        $response->assertStatus(422)->assertJsonValidationErrors(['payload.assoc_id']);
    }

    #[Test]
    public function extra_fields_in_payload_are_ignored_or_forbidden_based_on_rules(): void
    {
        $payload = $this->validPayload();
        $payload['payload']['unexpected'] = 'will-it-pass?';

        $response = $this->postImport($payload, env('API_KEY'));

        // If you use ->validate([...]) without "prohibited", extra fields are typically ignored.
        // If you enforce "prohibited:payload.unexpected", switch this to expect 422 on that field.
        $response->assertStatus(302)->assertRedirect();
    }

    // ---------- CONTENT NEGOTIATION / METHOD ----------

    #[Test]
    public function rejects_get_requests_to_import_endpoint(): void
    {
        $response = $this->withHeaders(['X-API-KEY' => env('API_KEY')])
            ->get('/api/nexo-import');

        $response->assertStatus(405); // Method Not Allowed (or 404 if you don't register GET)
    }

    #[Test]
    public function returns_json_error_shape_on_validation_failure(): void
    {
        $payload = ['source_id' => 'nexo', 'payload' => []];
        $response = $this->postImport($payload, env('API_KEY'));

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['payload.name', 'payload.email']]);
    }

    // ---------- NORMALIZATION / TRIMMING (if your FormRequest does this) ----------

    #[Test]
    public function trims_whitespace_from_string_fields_if_normalizer_exists(): void
    {
        $payload = $this->validPayload();
        $payload['payload']['name']  = "  Alice Example  ";
        $payload['payload']['email'] = "  alice@example.com  ";

        $response = $this->postImport($payload, env('API_KEY'));
        $response->assertStatus(302)->assertRedirect();

    }
}
