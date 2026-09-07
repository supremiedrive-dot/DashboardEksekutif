<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebUiTest extends TestCase
{
    public function test_guest_can_open_login_and_is_redirected_from_dashboard(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk Dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_requires_csrf_and_credentials(): void
    {
        $this->from('/login')->post('/login', [])->assertRedirect('/login')->assertSessionHasErrors(['email', 'password']);
    }

    public function test_dashboard_filter_url_is_available_to_authenticated_route(): void
    {
        $this->get('/dashboard?submenu=1&region=2&date=2026-08-04')->assertRedirect('/login');
    }

    public function test_filter_parameters_are_preserved_for_guest_redirect(): void
    {
        $this->get('/dashboard?submenu=2&region=2&date=2026-08-04')->assertRedirect('/login');
    }

    public function test_login_page_contains_secure_form(): void
    {
        $this->get('/login')->assertOk()->assertSee('name="email"', false)->assertSee('name="password"', false);
    }
}
