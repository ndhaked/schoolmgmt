<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/login', navigate: true);
    }
}; ?>

<div class="flex items-center gap-3" x-data="{ open: false }">
    <span class="hidden sm:inline-flex text-xs font-medium px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 capitalize">
        {{ auth()->user()->roles->first()?->name }}
    </span>

    <div class="relative">
        <button @click="open = !open" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-50">
            <span class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-sm font-semibold">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </span>
            <span class="hidden sm:inline text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            x-show="open"
            x-cloak
            @click.outside="open = false"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 ring-1 ring-black/5"
        >
            <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" wire:navigate>
                Profile
            </a>
            <button wire:click="logout" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                Log Out
            </button>
        </div>
    </div>
</div>
