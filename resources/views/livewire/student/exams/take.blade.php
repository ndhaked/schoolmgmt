<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\QuestionOption;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public Exam $exam;
    public ?ExamAttempt $attempt = null;
    public int $currentIndex = 0;
    public array $selectedOptions = [];
    public bool $finished = false;

    public function mount(Exam $exam): void
    {
        $student = auth()->user()->student;

        abort_unless(
            $student && $exam->school_class_id === $student->school_class_id && $exam->status === 'published',
            403
        );

        $this->exam = $exam;

        $attempt = ExamAttempt::firstOrCreate(
            ['exam_id' => $exam->id, 'student_id' => $student->id],
            ['started_at' => now(), 'status' => 'in_progress']
        );

        if ($attempt->status === 'submitted') {
            $this->finished = true;
            $this->attempt = $attempt;
            return;
        }

        if ($attempt->isExpired()) {
            $this->finalize($attempt);
            $this->finished = true;
            $this->attempt = $attempt->fresh();
            return;
        }

        $this->attempt = $attempt;
        $this->selectedOptions = $attempt->answers()->pluck('selected_option_id', 'question_id')->all();
    }

    public function with(): array
    {
        return [
            'questions' => $this->exam->questions()->with('options')->orderBy('id')->get(),
        ];
    }

    public function selectOption(int $questionId, int $optionId): void
    {
        if ($this->finished || $this->attempt->status !== 'in_progress') {
            return;
        }

        if ($this->attempt->isExpired()) {
            $this->submitExam();
            return;
        }

        $option = QuestionOption::findOrFail($optionId);
        $question = $option->question;

        $marksAwarded = $option->is_correct ? $question->marks : -$question->negative_marks;

        $this->attempt->answers()->updateOrCreate(
            ['question_id' => $questionId],
            [
                'selected_option_id' => $optionId,
                'is_correct' => $option->is_correct,
                'marks_awarded' => $marksAwarded,
            ]
        );

        $this->selectedOptions[$questionId] = $optionId;
    }

    public function goTo(int $index): void
    {
        $this->currentIndex = $index;
    }

    public function next(): void
    {
        $this->currentIndex = min($this->currentIndex + 1, $this->exam->questions()->count() - 1);
    }

    public function previous(): void
    {
        $this->currentIndex = max($this->currentIndex - 1, 0);
    }

    public function checkExpiry(): void
    {
        if (! $this->finished && $this->attempt->isExpired()) {
            $this->submitExam();
        }
    }

    public function submitExam(): void
    {
        if ($this->finished) {
            return;
        }

        $this->finalize($this->attempt);
        $this->finished = true;
        $this->attempt = $this->attempt->fresh();
    }

    private function finalize(ExamAttempt $attempt): void
    {
        $total = (float) $attempt->answers()->sum('marks_awarded');

        $attempt->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'obtained_marks' => max($total, 0),
        ]);
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">{{ $exam->title }}</h1>
    </x-slot>

    @if ($finished)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center max-w-lg mx-auto">
            <span class="mx-auto w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            <h2 class="text-lg font-semibold text-gray-900">Exam Submitted</h2>
            <p class="mt-2 text-sm text-gray-500">
                Your answers have been recorded. Results will be available once your teacher declares them.
            </p>
            <a href="{{ route('student.exams') }}" wire:navigate class="inline-block mt-6 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                Back to My Exams
            </a>
        </div>
    @else
        <div
            wire:poll.10s="checkExpiry"
            x-data="{
                deadline: new Date('{{ $attempt->deadline()->toIso8601String() }}').getTime(),
                remaining: 0,
                tick() {
                    this.remaining = Math.max(0, Math.floor((this.deadline - Date.now()) / 1000));
                    if (this.remaining <= 0) { $wire.submitExam(); }
                },
                formatted() {
                    let m = Math.floor(this.remaining / 60), s = this.remaining % 60;
                    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                }
            }"
            x-init="tick(); setInterval(() => tick(), 1000)"
        >
            <div class="flex items-center justify-between bg-white rounded-xl border border-gray-200 shadow-sm px-5 py-3 mb-4">
                <p class="text-sm text-gray-500">{{ $exam->schoolClass->name }} · {{ $exam->subject->name }}</p>
                <div class="flex items-center gap-2 text-sm font-semibold" :class="remaining <= 60 ? 'text-red-600' : 'text-gray-900'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="formatted()"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-3 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    @php $question = $questions[$currentIndex] ?? null; @endphp

                    @if ($question)
                        <p class="text-xs font-medium text-gray-400 mb-2">Question {{ $currentIndex + 1 }} of {{ $questions->count() }} · {{ $question->marks }} marks</p>
                        <p class="text-base text-gray-900 mb-5">{{ $question->question_text }}</p>

                        <div class="space-y-2">
                            @foreach ($question->options as $option)
                                <button
                                    type="button"
                                    wire:click="selectOption({{ $question->id }}, {{ $option->id }})"
                                    wire:key="option-{{ $option->id }}"
                                    class="w-full flex items-center gap-3 p-3 rounded-lg border text-left text-sm transition {{ ($selectedOptions[$question->id] ?? null) === $option->id ? 'border-indigo-500 bg-indigo-50 text-indigo-900' : 'border-gray-200 hover:bg-gray-50 text-gray-700' }}"
                                >
                                    <span class="shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center {{ ($selectedOptions[$question->id] ?? null) === $option->id ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300' }}">
                                        @if (($selectedOptions[$question->id] ?? null) === $option->id)
                                            <span class="w-2 h-2 rounded-full bg-white"></span>
                                        @endif
                                    </span>
                                    {{ $option->option_text }}
                                </button>
                            @endforeach
                        </div>

                        <div class="flex justify-between mt-6">
                            <button
                                wire:click="previous"
                                @disabled($currentIndex === 0)
                                class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                            >Previous</button>

                            @if ($currentIndex < $questions->count() - 1)
                                <button wire:click="next" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                    Next
                                </button>
                            @else
                                <button
                                    wire:click="submitExam"
                                    wire:confirm="Submit your exam? You won't be able to change answers after this."
                                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700"
                                >Submit Exam</button>
                            @endif
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-10">This exam has no questions.</p>
                    @endif
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 h-fit">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Questions</p>
                    <div class="grid grid-cols-5 lg:grid-cols-4 gap-2">
                        @foreach ($questions as $index => $q)
                            <button
                                wire:click="goTo({{ $index }})"
                                wire:key="nav-{{ $q->id }}"
                                class="w-9 h-9 rounded-lg text-xs font-semibold flex items-center justify-center
                                    {{ $index === $currentIndex ? 'ring-2 ring-indigo-500' : '' }}
                                    {{ isset($selectedOptions[$q->id]) ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}"
                            >
                                {{ $index + 1 }}
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500 space-y-1">
                        <p><span class="inline-block w-2.5 h-2.5 rounded bg-emerald-100 mr-1.5"></span> Answered</p>
                        <p><span class="inline-block w-2.5 h-2.5 rounded bg-gray-100 mr-1.5"></span> Not answered</p>
                    </div>

                    <button
                        wire:click="submitExam"
                        wire:confirm="Submit your exam? You won't be able to change answers after this."
                        class="w-full mt-4 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700"
                    >Submit Exam</button>
                </div>
            </div>
        </div>
    @endif
</div>
