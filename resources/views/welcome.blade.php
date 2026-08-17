<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>School Management & Online Examination System | Laravel Expert</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <meta name="description" content="Complete School Management & Online Examination System built on Laravel — Admin, Teacher, Student and Parent panels. Live online MCQ exams with auto-evaluation, instant results, and printable marksheets. Real-time, no page reloads.">
        <meta name="keywords" content="school management system, school management software, online examination system, online exam software, school ERP software, student information system, student result management system, online mcq exam software, exam management software, question bank software, school admission software, class attendance software, teacher management software, parent portal software, school management system india, laravel school management system, school software development, custom school erp development, school management system for coaching institute, online test software for schools, student marksheet software, result declaration software, school management system with student login, school management system with parent login">
        <meta name="geo.region" content="IN">
        <meta name="geo.placename" content="India">
        <meta name="author" content="Nirbhay Dhaked - LaravelExpert.in">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ url('/') }}">

        <!-- Open Graph / Facebook, WhatsApp, LinkedIn previews -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:title" content="School Management & Online Examination System">
        <meta property="og:description" content="Complete School Management & Online Examination System — Admin, Teacher, Student and Parent panels. Live online MCQ exams with auto-evaluation, instant results, and printable marksheets.">
        <meta property="og:site_name" content="SkoolMS">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="School Management & Online Examination System">
        <meta name="twitter:description" content="Complete School Management & Online Examination System — live online MCQ exams, auto-evaluation, instant results, printable marksheets.">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "School Management & Online Examination System",
            "applicationCategory": "EducationalApplication",
            "operatingSystem": "Web",
            "description": "Complete School Management & Online Examination System with Admin, Teacher, Student and Parent panels. Live online MCQ exams with auto-evaluation, instant results, and printable marksheets.",
            "offers": {
                "@type": "Offer",
                "priceCurrency": "INR"
            },
            "author": {
                "@type": "Organization",
                "name": "LaravelExpert.in",
                "url": "https://laravelexpert.in"
            }
        }
        </script>
    </head>
    <body class="antialiased font-sans bg-gray-50 text-gray-900">

        @php
            $whatsappNumber = '918209990511';
            $whatsappMessage = rawurlencode("Hi, I'm interested in the SkoolMS software.");
            $email = 'nirbhaydhaked@gmail.com';
        @endphp

        <!-- Header -->
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <x-logo-mark class="h-11 w-11" />
                    <span class="font-semibold text-gray-900">SkoolMS</span>
                </div>
                <nav class="flex items-center gap-4">
                    <a href="https://wa.me/{{ $whatsappNumber }}?text={{ $whatsappMessage }}" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-emerald-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M12.04 2c-5.52 0-10 4.48-10 10 0 1.77.46 3.44 1.26 4.89L2 22l5.25-1.38A9.96 9.96 0 0012.04 22c5.52 0 10-4.48 10-10s-4.48-10-10-10zm0 18.15c-1.6 0-3.1-.44-4.38-1.2l-.31-.19-3.12.82.83-3.04-.2-.32a8.14 8.14 0 01-1.26-4.32c0-4.51 3.67-8.18 8.18-8.18s8.18 3.67 8.18 8.18-3.67 8.18-8.18 8.18z"/></svg>
                        WhatsApp
                    </a>
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Log in</a>
                        <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700">Try Demo</a>
                    @endif
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-white"></div>
            <div class="relative max-w-6xl mx-auto px-6 py-20 text-center">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 mb-5">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-600"></span>
                    </span>
                    Live Online Exams · Instant Auto-Evaluation · Zero Paperwork
                </span>
                <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-gray-900">
                    School Management &amp; Online<br class="hidden sm:block"> Examination System
                </h1>
                <p class="mt-5 text-lg text-gray-600 max-w-2xl mx-auto">
                    One platform for Admins, Teachers, Students and Parents — manage classes, take attendance-ready
                    academic structure, run online MCQ exams with auto-evaluation, and declare results with a
                    printable marksheet. Everything updates live, with no page refreshes.
                </p>
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                    @if (Route::has('login'))
                        <a
                            href="{{ route('login') }}" wire:navigate
                            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 shadow-sm"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                            Try Live Demo
                        </a>
                    @endif
                    <a
                        href="https://wa.me/{{ $whatsappNumber }}?text={{ $whatsappMessage }}"
                        target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M12.04 2c-5.52 0-10 4.48-10 10 0 1.77.46 3.44 1.26 4.89L2 22l5.25-1.38A9.96 9.96 0 0012.04 22c5.52 0 10-4.48 10-10s-4.48-10-10-10zm0 18.15c-1.6 0-3.1-.44-4.38-1.2l-.31-.19-3.12.82.83-3.04-.2-.32a8.14 8.14 0 01-1.26-4.32c0-4.51 3.67-8.18 8.18-8.18s8.18 3.67 8.18 8.18-3.67 8.18-8.18 8.18z"/></svg>
                        Chat on WhatsApp
                    </a>
                    <a
                        href="mailto:{{ $email }}?subject={{ rawurlencode('Enquiry: SkoolMS') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-700 text-sm font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                        Email Us
                    </a>
                </div>
                <p class="mt-3 text-xs text-gray-400">Demo login credentials are shown right on the login page — no signup needed.</p>
                <p class="mt-4 text-sm text-gray-500">
                    📞 +91 {{ substr($whatsappNumber, 2, 5) }} {{ substr($whatsappNumber, 7) }}
                    &nbsp;·&nbsp;
                    ✉️ {{ $email }}
                </p>
            </div>
        </section>

        <!-- Panels -->
        <section class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="text-2xl font-bold text-center text-gray-900 mb-2">Four Panels, One System</h2>
            <p class="text-center text-gray-500 mb-10">Each role logs in to see only what's relevant to them.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <span class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>
                    </span>
                    <h3 class="font-semibold text-gray-900">Admin Panel</h3>
                    <p class="mt-1.5 text-sm text-gray-600">Academic years, classes, sections, subjects, students, teachers, parents, question bank, exams &amp; results — full control.</p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <span class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347M12 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                    </span>
                    <h3 class="font-semibold text-gray-900">Teacher Panel</h3>
                    <p class="mt-1.5 text-sm text-gray-600">Manage only their assigned classes/subjects: question bank, create &amp; publish exams, view class results.</p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <span class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                    </span>
                    <h3 class="font-semibold text-gray-900">Student Panel</h3>
                    <p class="mt-1.5 text-sm text-gray-600">Take online exams with a live timer &amp; auto-save, then view results and a printable marksheet.</p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <span class="w-10 h-10 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                    </span>
                    <h3 class="font-semibold text-gray-900">Parent Panel</h3>
                    <p class="mt-1.5 text-sm text-gray-600">View their child's declared results and marksheet — nothing else, nothing hidden either.</p>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="bg-white border-y border-gray-200">
            <div class="max-w-6xl mx-auto px-6 py-16">
                <h2 class="text-2xl font-bold text-center text-gray-900 mb-10">What Makes It Different</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ([
                        ['title' => 'Real-Time, No Reloads', 'desc' => 'Every list, form, and modal — even the exam countdown timer — updates live. Feels like a modern app, not a stack of PHP pages.'],
                        ['title' => 'Online MCQ Exams', 'desc' => 'Live countdown timer, instant answer autosave, and auto-submit when time runs out. Students never lose progress.'],
                        ['title' => 'Automatic Evaluation', 'desc' => 'MCQ answers are scored the instant they\'re selected, including configurable negative marking — no manual grading.'],
                        ['title' => 'Controlled Result Declaration', 'desc' => 'Scores stay hidden from students/parents until a teacher explicitly "Declares" the exam — no early leaks.'],
                        ['title' => 'Printable Marksheet', 'desc' => 'A clean report card aggregating every declared exam per student, with letter grades and an overall Pass/Fail.'],
                        ['title' => 'Role-Based Security', 'desc' => 'Every restriction is enforced on the server — a teacher can never touch a class they aren\'t assigned to, by design.'],
                    ] as $feature)
                        <div>
                            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ $feature['title'] }}
                            </h3>
                            <p class="mt-1.5 text-sm text-gray-600">{{ $feature['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="max-w-4xl mx-auto px-6 py-16 text-center">
            <h2 class="text-2xl font-bold text-gray-900">Want this for your school?</h2>
            <p class="mt-2 text-gray-600">Get in touch for a live demo, pricing, or a custom setup for your institution.</p>
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a
                    href="https://wa.me/{{ $whatsappNumber }}?text={{ $whatsappMessage }}"
                    target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 shadow-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M12.04 2c-5.52 0-10 4.48-10 10 0 1.77.46 3.44 1.26 4.89L2 22l5.25-1.38A9.96 9.96 0 0012.04 22c5.52 0 10-4.48 10-10s-4.48-10-10-10zm0 18.15c-1.6 0-3.1-.44-4.38-1.2l-.31-.19-3.12.82.83-3.04-.2-.32a8.14 8.14 0 01-1.26-4.32c0-4.51 3.67-8.18 8.18-8.18s8.18 3.67 8.18 8.18-3.67 8.18-8.18 8.18z"/></svg>
                    +91 82099 90511
                </a>
                <a
                    href="mailto:{{ $email }}?subject={{ rawurlencode('Enquiry: SkoolMS') }}"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-700 text-sm font-semibold rounded-lg border border-gray-300 hover:bg-gray-50 shadow-sm"
                >
                    {{ $email }}
                </a>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-200 py-6">
            <p class="text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} SkoolMS ·
                Developed by <a href="https://laravelexpert.in" target="_blank" rel="noopener" class="text-gray-500 hover:text-indigo-600 underline">laravelexpert.in</a>
                — Nirbhay Dhaked
            </p>
        </footer>

        <!-- Floating WhatsApp button -->
        <a
            href="https://wa.me/{{ $whatsappNumber }}?text={{ $whatsappMessage }}"
            target="_blank" rel="noopener"
            class="fixed bottom-5 right-5 w-14 h-14 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow-lg hover:bg-emerald-600"
            aria-label="Chat on WhatsApp"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-7 h-7"><path d="M12.04 2c-5.52 0-10 4.48-10 10 0 1.77.46 3.44 1.26 4.89L2 22l5.25-1.38A9.96 9.96 0 0012.04 22c5.52 0 10-4.48 10-10s-4.48-10-10-10zm0 18.15c-1.6 0-3.1-.44-4.38-1.2l-.31-.19-3.12.82.83-3.04-.2-.32a8.14 8.14 0 01-1.26-4.32c0-4.51 3.67-8.18 8.18-8.18s8.18 3.67 8.18 8.18-3.67 8.18-8.18 8.18z"/></svg>
        </a>
    </body>
</html>
