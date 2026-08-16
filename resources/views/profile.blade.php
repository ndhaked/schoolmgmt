<x-layouts.panel :title="'Profile'">
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Profile</h1>
    </x-slot>

    <div class="space-y-6 max-w-2xl">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <livewire:profile.update-profile-information-form />
        </div>

        {{-- Temporarily disabled for now — re-enable when ready.
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <livewire:profile.update-password-form />
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <livewire:profile.delete-user-form />
        </div>
        --}}
    </div>
</x-layouts.panel>
