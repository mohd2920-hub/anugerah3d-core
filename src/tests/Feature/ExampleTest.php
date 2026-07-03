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

    public function test_admin_domain_redirects_to_filament_panel(): void
    {
        $this->getFromDomain((string) config('domains.admin'))
            ->assertRedirect('/admin');
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
