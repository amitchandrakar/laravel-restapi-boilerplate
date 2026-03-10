<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure CORS config is applied in testing (env can be empty in phpunit)
        Config::set('cors.allowed_origins', ['*']);
        Config::set('cors.supports_credentials', false);
    }

    /**
     * CORS headers are present on API GET response when Origin is sent.
     */
    public function test_cors_headers_on_api_get_request(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://app.example.com',
        ])->getJson('/api/health');

        $response->assertStatus(200);

        $this->assertTrue(
            $response->headers->has('Access-Control-Allow-Origin'),
            'Response should include Access-Control-Allow-Origin header'
        );
    }

    /**
     * OPTIONS preflight to an API path returns 204 and CORS headers.
     */
    public function test_cors_preflight_options_request(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://app.example.com',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/health');

        $response->assertStatus(204);

        $this->assertTrue(
            $response->headers->has('Access-Control-Allow-Origin'),
            'Preflight response should include Access-Control-Allow-Origin'
        );
        $this->assertTrue(
            $response->headers->has('Access-Control-Allow-Methods'),
            'Preflight response should include Access-Control-Allow-Methods'
        );
    }

    /**
     * CORS applies to sanctum/csrf-cookie path.
     */
    public function test_cors_headers_on_sanctum_csrf_cookie_path(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://app.example.com',
        ])->get('/sanctum/csrf-cookie');

        $this->assertContains($response->status(), [200, 204]);

        $this->assertTrue(
            $response->headers->has('Access-Control-Allow-Origin'),
            'sanctum/csrf-cookie should have CORS headers'
        );
    }
}
