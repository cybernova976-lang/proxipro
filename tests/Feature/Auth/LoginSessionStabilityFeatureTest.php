<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginSessionStabilityFeatureTest extends TestCase
{
    public function test_csrf_token_can_be_refreshed_without_using_a_cached_response(): void
    {
        $response = $this->getJson(route('auth.csrf.refresh'));

        $response->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertNotEmpty($response->json('token'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_login_form_refreshes_csrf_token_before_submission(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(route('login.attempt'), false);
        $response->assertSee('auth\/csrf-token', false);
        $response->assertSee('HTMLFormElement.prototype.submit.call(loginForm);', false);
    }
}
