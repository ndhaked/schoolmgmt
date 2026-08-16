<x-layouts.panel :title="'Teacher Dashboard'">
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Teacher Dashboard</h1>
    </x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <p class="text-gray-600 text-sm leading-relaxed">
            Welcome, <span class="font-medium text-gray-900">{{ auth()->user()->name }}</span>. Your assigned
            classes, question bank, and exam creation tools will appear here.
        </p>
    </div>
</x-layouts.panel>
