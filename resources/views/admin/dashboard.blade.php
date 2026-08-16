<x-layouts.panel :title="'Admin Dashboard'">
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Admin Dashboard</h1>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <x-icon name="academic-cap" class="w-5 h-5" />
                </span>
                <div>
                    <p class="text-xs font-medium text-gray-500">Total Students</p>
                    <p class="text-2xl font-semibold text-gray-900">—</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <x-icon name="user-group" class="w-5 h-5" />
                </span>
                <div>
                    <p class="text-xs font-medium text-gray-500">Total Teachers</p>
                    <p class="text-2xl font-semibold text-gray-900">—</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <x-icon name="clipboard" class="w-5 h-5" />
                </span>
                <div>
                    <p class="text-xs font-medium text-gray-500">Active Exams</p>
                    <p class="text-2xl font-semibold text-gray-900">—</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                    <x-icon name="chart" class="w-5 h-5" />
                </span>
                <div>
                    <p class="text-xs font-medium text-gray-500">Results Declared</p>
                    <p class="text-2xl font-semibold text-gray-900">—</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <p class="text-gray-600 text-sm leading-relaxed">
            Welcome, <span class="font-medium text-gray-900">{{ auth()->user()->name }}</span>. This is the Admin
            panel shell — Academic Years, Classes, Students, Teachers, Question Bank, and Exam modules will plug
            into this sidebar as they're built.
        </p>
    </div>
</x-layouts.panel>
