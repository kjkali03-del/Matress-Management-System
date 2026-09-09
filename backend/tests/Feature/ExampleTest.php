<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_starts_at_the_login_page(): void
    {
        $this->get('/')->assertRedirectToRoute('login');
        $this->get('/login')->assertOk()->assertSee('Admin Login');
    }

    public function test_the_admin_page_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirectToRoute('login');
    }
}
