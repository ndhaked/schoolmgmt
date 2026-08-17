<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public function with(): array
    {
        $student = auth()->user()->student;

        $exams = Exam::where('school_class_id', $student->school_class_id)
            ->where('status', 'published')
            ->with('questions')
            ->orderBy('starts_at')
            ->get();

        $attempts = ExamAttempt::where('student_id', $student->id)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->keyBy('exam_id');

        return [
            'exams' => $exams,
            'attempts' => $attempts,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">My Exams</h1>
    </x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Exam</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Schedule</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Questions</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($exams as $exam)
                    @php
                        $attempt = $attempts->get($exam->id);
                        $now = now();
                        $isLive = $now->between($exam->starts_at, $exam->ends_at);
                        $isUpcoming = $now->lessThan($exam->starts_at);
                        $isMissed = $now->greaterThan($exam->ends_at);
                    @endphp
                    <tr wire:key="exam-{{ $exam->id }}">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $exam->title }}</td>
                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                            {{ $exam->starts_at->format('d M Y, h:i A') }}
                            <span class="text-gray-400">({{ $exam->duration_minutes }} min)</span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $exam->questions->count() }} questions · {{ $exam->totalMarks() }} marks</td>
                        <td class="px-6 py-4">
                            @if ($attempt?->status === 'submitted')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Submitted</span>
                            @elseif ($attempt?->status === 'in_progress')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">In Progress</span>
                            @elseif ($isMissed)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Missed</span>
                            @elseif ($isUpcoming)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700">Upcoming</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Live</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if ($attempt?->status === 'submitted')
                                <span class="text-gray-400 text-xs">Awaiting result</span>
                            @elseif ($attempt?->status === 'in_progress')
                                <a href="{{ route('student.exams.take', $exam) }}" wire:navigate class="text-indigo-600 hover:text-indigo-800 font-medium">Resume</a>
                            @elseif ($isLive)
                                <a href="{{ route('student.exams.take', $exam) }}" wire:navigate class="text-indigo-600 hover:text-indigo-800 font-medium">Start Exam</a>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            No exams have been published for your class yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
