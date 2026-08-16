<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public array $demoAccounts = [
        ['label' => 'Admin', 'email' => 'admin@school.test', 'password' => 'password'],
        ['label' => 'Teacher', 'email' => 'teacher@school.test', 'password' => 'password'],
        ['label' => 'Student', 'email' => 'student1@school.test', 'password' => 'password'],
        ['label' => 'Parent', 'email' => 'parent@school.test', 'password' => 'password'],
    ];

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    public function fillDemoAccount(string $email, string $password): void
    {
        $this->form->email = $email;
        $this->form->password = $password;
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t border-gray-200">
        <p class="text-xs font-medium text-gray-500 mb-2">Demo accounts — click to autofill</p>
        <div class="grid grid-cols-2 gap-2">
            @foreach ($demoAccounts as $account)
                <button
                    type="button"
                    wire:click="fillDemoAccount('{{ $account['email'] }}', '{{ $account['password'] }}')"
                    class="text-left px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 text-xs"
                >
                    <span class="block font-medium text-gray-700">{{ $account['label'] }}</span>
                    <span class="block text-gray-400">{{ $account['email'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
