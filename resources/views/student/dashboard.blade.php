<x-layouts.panel :title="'Student Dashboard'">
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Student Dashboard</h1>
    </x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <p class="text-gray-600 text-sm leading-relaxed">
            Welcome, <span class="font-medium text-gray-900">{{ auth()->user()->name }}</span>. Your upcoming
            exams, online exam-taking, and results will appear here.
        </p>
    </div>
</x-layouts.panel>
