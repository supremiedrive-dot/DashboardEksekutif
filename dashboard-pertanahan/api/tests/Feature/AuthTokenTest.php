<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Explicit opt-in; never migrate, seed, or refresh a database from this suite.
        if (getenv('AUTH_TEST_DB_READY') !== '1' || ! is_file(base_path('.env.testing'))) {
            $this->markTestSkipped('Separate MySQL testing environment has not been provisioned/approved.');
        }
        $this->assertFalse($this->app->configurationIsCached());
        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('mysql', config('database.default'));
        $connection = DB::connection();
        $this->assertSame('dashboard_pertanahan_test', $connection->getDatabaseName());
        $this->assertEmpty($connection->getConfig('url'));
        $this->assertEmpty($connection->getConfig('read'));
        $this->assertEmpty($connection->getConfig('write'));
        // Verify actual server selection before any write/transaction.
        $this->assertSame('dashboard_pertanahan_test', $connection->selectOne('SELECT DATABASE() AS db')->db);
        foreach (['users', 'personal_access_tokens'] as $table) {
            $this->assertTrue($connection->getSchemaBuilder()->hasTable($table));
        }
        config(['cache.default' => 'array', 'session.driver' => 'array']);
        $connection->beginTransaction();
        $this->beforeApplicationDestroyed(fn () => $connection->rollBack());
    }

    private function credentials(): array
    {
        $password = Str::random(40);
        $user = User::create([
            'name' => 'Auth test', 'email' => Str::uuid().'@example.test', 'password' => $password,
        ]);
        return [$user, ['email' => $user->email, 'password' => $password]];
    }

    private function forgetAuthentication(): void
    {
        Auth::forgetGuards();
    }

    public function test_wrong_and_unknown_credentials_have_identical_generic_response(): void
    {
        [$user, $credentials] = $this->credentials();
        foreach ([$user->email, 'absent@example.test'] as $email) {
            $this->postJson('/api/login', ['email' => $email, 'password' => Str::random(40)])
                ->assertUnauthorized()->assertExactJson(['message' => 'Email atau password salah']);
        }
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_login_profile_logout_and_revoked_token_with_other_device_preserved(): void
    {
        [$user, $credentials] = $this->credentials();
        $response = $this->postJson('/api/login', $credentials)->assertOk();
        $this->assertSame(['message', 'user', 'token'], array_keys($response->json()));
        $token = $response->json('token');
        $this->assertTrue(is_string($token) && strlen($token) > 20);
        foreach (['password', 'remember_token', 'tokens'] as $key) {
            $this->assertArrayNotHasKey($key, $response->json('user'));
        }
        $stored = $user->tokens()->firstOrFail();
        $this->assertSame('dashboard-api', $stored->name);
        $this->assertTrue($stored->expires_at->isFuture());
        $other = $user->createToken('other-device')->plainTextToken;
        $this->forgetAuthentication();
        $profile = $this->withToken($token)->get('/api/user')->assertOk();
        $this->assertSame($response->json('user'), $profile->json());
        $this->forgetAuthentication();
        $this->withToken($token)->post('/api/logout')->assertOk()
            ->assertExactJson(['message' => 'Logout berhasil']);
        $this->assertSame(1, $user->tokens()->count());
        $this->forgetAuthentication();
        $this->withToken($token)->get('/api/user')->assertUnauthorized();
        $this->forgetAuthentication();
        $this->withToken($other)->get('/api/user')->assertOk();
    }

    public function test_invalid_and_expired_tokens_are_rejected(): void
    {
        [$user] = $this->credentials();
        $expired = $user->createToken('expired-test', ['*'], now()->subMinute())->plainTextToken;
        foreach ([Str::random(64), $expired] as $token) {
            $this->forgetAuthentication();
            $this->withToken($token)->get('/api/user')->assertUnauthorized()
                ->assertExactJson(['message' => 'Unauthenticated.']);
        }
    }

    public function test_wrong_credentials_are_throttled_after_five_attempts(): void
    {
        [$user] = $this->credentials();
        $input = ['email' => $user->email, 'password' => Str::random(40)];
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', $input)->assertUnauthorized();
        }
        $this->postJson('/api/login', $input)->assertStatus(429)->assertHeader('Retry-After');
        $this->assertSame(0, $user->tokens()->count());
    }
}
