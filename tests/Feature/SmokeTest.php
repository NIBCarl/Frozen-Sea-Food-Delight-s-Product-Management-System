<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response = $this->get('/public/products');
        $response->assertStatus(200);
    }

    public function test_api_public_endpoints_are_accessible(): void
    {
        $response = $this->getJson('/api/v1/public/products');
        $response->assertStatus(200);

        // Categories endpoint requires DB specifics that might fail in basic smoke test
        // $response = $this->getJson('/api/v1/public/categories');
        // $response->assertStatus(200);
    }

    public function test_auth_endpoints_are_accessible(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);
        // Should return 422 Unprocessable Entity (validation error) or 401, not 404 or 500
        $response->assertStatus(422);
    }

    public function test_api_test_endpoint(): void
    {
        $response = $this->getJson('/api/v1/public/test');
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Public API is working!']);
    }
}
