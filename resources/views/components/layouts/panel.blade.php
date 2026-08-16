<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'School Management System') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gray-50" x-data="{ sidebarOpen: false }">
        <div class="min-h-screen flex">

            <!-- Sidebar -->
            <aside
                class="print:hidden fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <div class="h-16 flex items-center gap-2 px-6 border-b border-gray-200 shrink-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                    <span class="text-base font-semibold tracking-tight text-gray-900">{{ config('app.name') }}</span>
                </div>

                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">
                    @php $user = auth()->user(); @endphp

                    @if ($user->hasRole('admin'))
                        <x-panel.nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            <x-slot name="icon"><x-icon name="home" class="w-5 h-5" /></x-slot>
                            Dashboard
                        </x-panel.nav-link>

                        <p class="px-3 pt-5 pb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">Academics</p>
                        <x-panel.nav-link :href="route('admin.academic-years')" :active="request()->routeIs('admin.academic-years')">
                            <x-slot name="icon"><x-icon name="calendar" class="w-5 h-5" /></x-slot>
                            Academic Years
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('admin.classes')" :active="request()->routeIs('admin.classes')">
                            <x-slot name="icon"><x-icon name="building" class="w-5 h-5" /></x-slot>
                            Classes &amp; Sections
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('admin.subjects')" :active="request()->routeIs('admin.subjects')">
                            <x-slot name="icon"><x-icon name="book" class="w-5 h-5" /></x-slot>
                            Subjects
                        </x-panel.nav-link>

                        <p class="px-3 pt-5 pb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">People</p>
                        <x-panel.nav-link :href="route('admin.students')" :active="request()->routeIs('admin.students')">
                            <x-slot name="icon"><x-icon name="academic-cap" class="w-5 h-5" /></x-slot>
                            Students
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('admin.teachers')" :active="request()->routeIs('admin.teachers')">
                            <x-slot name="icon"><x-icon name="user-group" class="w-5 h-5" /></x-slot>
                            Teachers
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('admin.parents')" :active="request()->routeIs('admin.parents')">
                            <x-slot name="icon"><x-icon name="users" class="w-5 h-5" /></x-slot>
                            Parents
                        </x-panel.nav-link>

                        <p class="px-3 pt-5 pb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">Examination</p>
                        <x-panel.nav-link :href="route('admin.question-bank')" :active="request()->routeIs('admin.question-bank')">
                            <x-slot name="icon"><x-icon name="clipboard" class="w-5 h-5" /></x-slot>
                            Question Bank
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('admin.exams')" :active="request()->routeIs('admin.exams')">
                            <x-slot name="icon"><x-icon name="book" class="w-5 h-5" /></x-slot>
                            Exams
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('admin.results')" :active="request()->routeIs('admin.results')">
                            <x-slot name="icon"><x-icon name="chart" class="w-5 h-5" /></x-slot>
                            Results
                        </x-panel.nav-link>
                    @elseif ($user->hasRole('teacher'))
                        <x-panel.nav-link :href="route('teacher.dashboard')" :active="request()->routeIs('teacher.dashboard')">
                            <x-slot name="icon"><x-icon name="home" class="w-5 h-5" /></x-slot>
                            Dashboard
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('teacher.classes')" :active="request()->routeIs('teacher.classes')">
                            <x-slot name="icon"><x-icon name="building" class="w-5 h-5" /></x-slot>
                            My Classes
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('teacher.question-bank')" :active="request()->routeIs('teacher.question-bank')">
                            <x-slot name="icon"><x-icon name="clipboard" class="w-5 h-5" /></x-slot>
                            Question Bank
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('teacher.exams')" :active="request()->routeIs('teacher.exams')">
                            <x-slot name="icon"><x-icon name="book" class="w-5 h-5" /></x-slot>
                            Exams
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('teacher.results')" :active="request()->routeIs('teacher.results')">
                            <x-slot name="icon"><x-icon name="chart" class="w-5 h-5" /></x-slot>
                            Results
                        </x-panel.nav-link>
                    @elseif ($user->hasRole('student'))
                        <x-panel.nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">
                            <x-slot name="icon"><x-icon name="home" class="w-5 h-5" /></x-slot>
                            Dashboard
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('student.exams')" :active="request()->routeIs('student.exams*')">
                            <x-slot name="icon"><x-icon name="book" class="w-5 h-5" /></x-slot>
                            My Exams
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('student.results')" :active="request()->routeIs('student.results')">
                            <x-slot name="icon"><x-icon name="chart" class="w-5 h-5" /></x-slot>
                            My Results
                        </x-panel.nav-link>
                    @elseif ($user->hasRole('parent'))
                        <x-panel.nav-link :href="route('parent.dashboard')" :active="request()->routeIs('parent.dashboard')">
                            <x-slot name="icon"><x-icon name="home" class="w-5 h-5" /></x-slot>
                            Dashboard
                        </x-panel.nav-link>
                        <x-panel.nav-link :href="route('parent.results')" :active="request()->routeIs('parent.results')">
                            <x-slot name="icon"><x-icon name="chart" class="w-5 h-5" /></x-slot>
                            Child's Results
                        </x-panel.nav-link>
                    @endif
                </nav>
            </aside>

            <!-- Overlay for mobile -->
            <div
                x-show="sidebarOpen"
                x-cloak
                @click="sidebarOpen = false"
                class="fixed inset-0 z-20 bg-black/30 lg:hidden"
            ></div>

            <!-- Main column -->
            <div class="flex-1 flex flex-col min-w-0 lg:pl-0">
                <!-- Topbar -->
                <header class="print:hidden h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-10">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        @isset($header)
                            {{ $header }}
                        @endisset
                    </div>

                    <livewire:layout.panel-topbar />
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
