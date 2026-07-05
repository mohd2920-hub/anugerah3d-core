<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_fallback_root_redirects_to_the_public_site(): void
    {
        $this->get('/')
            ->assertRedirect(config('domains.public_url'));
    }

    public function test_public_domain_returns_the_public_homepage(): void
    {
        $this->getFromDomain((string) config('domains.public'))
            ->assertOk()
            ->assertViewIs('public.home')
            ->assertSeeText('Anugerah3D')
            ->assertSeeText('Dari Idea Menjadi Realiti')
            ->assertSeeText('Product Categories')
            ->assertSeeText('How It Works')
            ->assertSeeText('WhatsApp Us');
    }

    public function test_public_alias_returns_the_public_homepage(): void
    {
        $this->getFromDomain((string) config('domains.public_aliases.www'))
            ->assertOk()
            ->assertViewIs('public.home');
    }

    public function test_admin_domain_redirects_to_login_page(): void
    {
        $this->getFromDomain((string) config('domains.admin'))
            ->assertRedirect('/login');
    }

    public function test_admin_login_page_links_to_dashboard(): void
    {
        $domain = (string) config('domains.admin');

        $this->get("http://{$domain}/login")
            ->assertOk()
            ->assertViewIs('admin.auth.login')
            ->assertSeeText('Admin Login')
            ->assertSeeText('Sign in');
    }

    public function test_admin_dashboard_page_displays_admin_navigation(): void
    {
        $domain = (string) config('domains.admin');

        $this->get("http://{$domain}/dashboard")
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSeeText('Admin Dashboard')
            ->assertSeeText('Revenue Trend')
            ->assertSeeText('Production Progress')
            ->assertSeeText('Top Customers')
            ->assertSeeText('Order Pipeline')
            ->assertSeeText('Order management');
    }

    public function test_agent_domain_returns_placeholder_portal_response(): void
    {
        $this->getFromDomain((string) config('domains.agent'))
            ->assertOk()
            ->assertSeeText('Anugerah3D Agent Portal Ready');
    }

    public function test_customer_domain_returns_placeholder_portal_response(): void
    {
        $this->getFromDomain((string) config('domains.customer'))
            ->assertOk()
            ->assertSeeText('Anugerah3D Customer Portal Ready');
    }

    private function getFromDomain(string $domain): TestResponse
    {
        return $this->get("http://{$domain}/");
    }
}
