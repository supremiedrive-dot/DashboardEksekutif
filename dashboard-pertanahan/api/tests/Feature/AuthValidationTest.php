<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'session.driver' => 'array']);
        DB::connection()->beforeExecuting(function () {
            throw new \LogicException('Database access forbidden in non-database auth tests.');
        });
    }

    public function test_login_validation_is_json_without_accept_header(): void
    {
        foreach ([[], ['email' => ['invalid'], 'password' => []],
            ['email' => 'invalid', 'password' => str_repeat('x', 4097)],
            ['email' => str_repeat('a', 256).'@example.test', 'password' => 'x']] as $input) {
            $response = $this->post('/api/login', $input);
            $response->assertStatus(422)->assertJsonValidationErrors(['email']);
            if (($input['password'] ?? null) !== 'x') {
                $response->assertJsonValidationErrors(['password']);
            }
            $this->assertArrayNotHasKey('token', $response->json());
        }
    }

    public function test_profile_and_logout_require_authentication_without_accept_header(): void
    {
        $this->get('/api/user')->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
        $this->post('/api/logout')->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_login_is_limited_and_identity_case_cannot_bypass_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/api/login', ['email' => 'limit@example.test'])->assertStatus(422);
        }
        $response = $this->post('/api/login', ['email' => 'LIMIT@example.test']);
        $response->assertStatus(429)->assertHeader('Retry-After');
        $this->assertArrayNotHasKey('token', $response->json());
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
            ->post('/api/login', ['email' => 'limit@example.test'])->assertStatus(422);
    }
}
