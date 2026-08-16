<?php

namespace Tests\Feature\Auth;

use Livewire\Volt\Volt;
use Tests\TestCase;

class LoginDemoAutofillTest extends TestCase
{
    public function test_clicking_a_demo_account_fills_the_login_form(): void
    {
        Volt::test('pages.auth.login')
            ->call('fillDemoAccount', 'admin@school.test', 'password')
            ->assertSet('form.email', 'admin@school.test')
            ->assertSet('form.password', 'password');
    }
}
