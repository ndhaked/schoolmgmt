<?php

use App\Models\ExamAttempt;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public function with(): array
    {
        $student = auth()->user()->student;

        $attempts = ExamAttempt::with(['exam.schoolClass', 'exam.subject', 'exam.questions'])
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->whereHas('exam', fn ($q) => $q->whereNotNull('results_declared_at'))
            ->get()
            ->sortByDesc(fn ($attempt) => $attempt->exam->results_declared_at);

        return ['attempts' => $attempts];
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">My Results</h1>
    </x-slot>

    <div class="flex justify-end mb-4">
        <a href="{{ route('student.marksheet') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
            View Marksheet
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Exam</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Marks</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Percentage</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Result</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($attempts as $attempt)
                    @php
                        $totalMarks = $attempt->exam->totalMarks();
                        $percentage = $totalMarks > 0 ? round(($attempt->obtained_marks / $totalMarks) * 100, 1) : 0;
                        $passed = $percentage >= $attempt->exam->pass_percentage;
                    @endphp
                    <tr wire:key="attempt-{{ $attempt->id }}">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $attempt->exam->title }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $attempt->exam->subject->name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $attempt->obtained_marks }} / {{ $totalMarks }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $percentage }}%</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $passed ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                {{ $passed ? 'Pass' : 'Fail' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            No results have been declared yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
