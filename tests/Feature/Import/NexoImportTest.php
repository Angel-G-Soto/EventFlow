<?php

namespace Tests\Feature\Import;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NexoImportTest extends TestCase
{
    protected string $apiKey = 'evf_live_Gd4bJHnCksU1cOzUba2vZVd0P3m7WQi-8F2y9tLrAcY';

    protected function setUp(): void
    {
        parent::setUp();

        // Stub target route so we don’t need a Blade view
        Route::get('/events/create', fn () => 'ok')->name('events.create');

        // Ensure allowed sources for the FormRequest Rule::in(...)
        Config::set('sources.allowed_sources', ['nexo.test']);

        // Since your middleware reads env('API_KEY') directly, set it here:
        putenv("API_KEY={$this->apiKey}");
        $_ENV['API_KEY'] = $this->apiKey;
        $_SERVER['API_KEY'] = $this->apiKey; // belt & suspenders
    }

    // ---- Debug helpers ---------------------------------------------------

    private function debugRedirectPayload($res, string $label = 'Redirect')
    {
        $location = $res->headers->get('Location') ?? '';
        fwrite(STDOUT, "\n=== {$label} Location ===\n{$location}\n");

        $qs = [];
        if ($location) {
            $query = parse_url($location, PHP_URL_QUERY) ?? '';
            parse_str($query, $qs);
        }
        fwrite(STDOUT, "=== {$label} Parsed Query ===\n" . print_r($qs, true) . "\n");
    }

    private function debugStatusAndBody($res, string $label = 'Response')
    {
        $status = $res->getStatusCode();
        $body   = $res->getContent();
        fwrite(STDOUT, "\n=== {$label} Status ===\n{$status}\n");
        fwrite(STDOUT, "=== {$label} Body ===\n{$body}\n");
    }

    private function debugSessionErrors($res, string $label = 'Session Errors')
    {
        $errorsBag = $res->getSession()?->get('errors');
        $errors = $errorsBag ? $errorsBag->toArray() : [];
        fwrite(STDOUT, "\n=== {$label} ===\n" . print_r($errors, true) . "\n");
    }

    // ---- Tests -----------------------------------------------------------

    #[Test]
    public function accepts_form_urlencoded_and_redirects_with_query()
    {
        $payload = [
            'source_id' => 'nexo.test',
            'payload' => [
                'name' => 'Andres Rosado',
                'email' => 'andres.rosado@upr.edu',
                'assoc_id' => 14,
                'association_name' => 'CTI',
                'counselor' => 'Martin Melendez',
                'email_counselor' => 'martin.melendez@upr.edu',
            ],
        ];

        $res = $this->withHeaders(['X-API-KEY' => $this->apiKey])
            ->post('/api/nexo-import', $payload);

        // Print redirect + parsed query
        $this->debugRedirectPayload($res, 'Form URL-encoded');

        // Assert
        $expectedUrl = route('events.create', $payload);
        $res->assertRedirect($expectedUrl);
    }

    #[Test]
    public function rejects_when_api_key_missing_or_wrong()
    {
        $res = $this->post('/api/nexo-import', [
            'source_id' => 'nexo.test',
            'payload' => [
                'name' => 'A',
                'email' => 'a@b.com',
                'assoc_id' => 1,
                'association_name' => 'X',
            ],
        ]);

        // Print status/body to see the 401 payload
        $this->debugStatusAndBody($res, 'Unauthorized');

        $res->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    #[Test]
    public function validates_source_id_against_config()
    {
        $res = $this->withHeaders(['X-API-KEY' => $this->apiKey])
            ->post('/api/nexo-import', [
                'source_id' => 'not-allowed',
                'payload' => [
                    'name' => 'A',
                    'email' => 'a@b.com',
                    'assoc_id' => 1,
                    'association_name' => 'X',
                ],
            ]);

        // Print redirect + parsed query (will likely be empty) and session errors
        $this->debugRedirectPayload($res, 'Validation Fail (source_id)');
        $this->debugSessionErrors($res, 'Validation Errors (source_id)');

        // For form-style requests, validation -> 302 back with errors in session
        $res->assertStatus(302);
        $res->assertSessionHasErrors('source_id');
    }

    #[Test]
    public function accepts_json_body_too()
    {
        $json = [
            'source_id' => 'nexo.test',
            'payload' => [
                'name' => 'Andres Rosado',
                'email' => 'andres.rosado@upr.edu',
                'assoc_id' => 14,
                'association_name' => 'CTI',
                'counselor' => 'Martin Melendez',
                'email_counselor' => 'martin.melendez@upr.edu',
            ],
        ];

        $res = $this->withHeaders([
            'X-API-KEY' => $this->apiKey,
            'Accept'    => 'application/json',
        ])->postJson('/api/nexo-import', $json);

        // Print redirect + parsed query for the JSON path
        $this->debugRedirectPayload($res, 'JSON Case');

        $res->assertStatus(302);
        $res->assertRedirect(route('events.create', $json));
    }
}
