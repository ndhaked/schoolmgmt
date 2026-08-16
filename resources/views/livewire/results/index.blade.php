<?php

use App\Models\Exam;
use App\Models\Student;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public bool $showModal = false;
    public ?int $viewingExamId = null;

    public function with(): array
    {
        $query = Exam::with(['schoolClass', 'subject', 'questions'])
            ->withCount(['attempts as submitted_count' => fn ($q) => $q->where('status', 'submitted')]);

        if (! auth()->user()->hasRole('admin')) {
            $query->where('created_by', auth()->id());
        }

        return [
            'exams' => $query->latest()->get(),
        ];
    }

    #[Computed]
    public function roster()
    {
        if (! $this->viewingExamId) {
            return collect();
        }

        $exam = Exam::with('questions')->findOrFail($this->viewingExamId);
        $totalMarks = $exam->totalMarks();

        return Student::where('school_class_id', $exam->school_class_id)
            ->with(['user', 'examAttempts' => fn ($q) => $q->where('exam_id', $exam->id)])
            ->orderBy('roll_number')
            ->get()
            ->map(function ($student) use ($totalMarks, $exam) {
                $attempt = $student->examAttempts->first();
                $percentage = $attempt && $attempt->status === 'submitted' && $totalMarks > 0
                    ? round(($attempt->obtained_marks / $totalMarks) * 100, 1)
                    : null;

                return (object) [
                    'student' => $student,
                    'attempt' => $attempt,
                    'percentage' => $percentage,
                    'passed' => $percentage !== null ? $percentage >= $exam->pass_percentage : null,
                ];
            });
    }

    #[Computed]
    public function classPerformance()
    {
        $submitted = $this->roster->filter(fn ($row) => $row->attempt?->status === 'submitted');

        if ($submitted->isEmpty()) {
            return null;
        }

        $percentages = $submitted->pluck('percentage');

        return [
            'average' => round($percentages->avg(), 1),
            'highest' => $percentages->max(),
            'lowest' => $percentages->min(),
            'pass_rate' => round($submitted->filter(fn ($row) => $row->passed)->count() / $submitted->count() * 100, 1),
        ];
    }

    public function viewResults(int $examId): void
    {
        $exam = Exam::findOrFail($examId);
        $this->authorizeManage($exam);

        $this->viewingExamId = $examId;
        unset($this->roster);
        unset($this->classPerformance);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function declare(): void
    {
        $exam = Exam::findOrFail($this->viewingExamId);
        $this->authorizeManage($exam);

        if (! $exam->canDeclare()) {
            $this->addError('declare', 'Results can only be declared after the exam ends and at least one student has submitted.');
            return;
        }

        $exam->update(['results_declared_at' => now()]);
    }

    private function authorizeManage(Exam $exam): void
    {
        abort_unless(auth()->user()->hasRole('admin') || $exam->created_by === auth()->id(), 403);
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Results</h1>
    </x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Exam</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class / Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Submitted</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($exams as $exam)
                    <tr wire:key="exam-{{ $exam->id }}">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $exam->title }}</td>
                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">{{ $exam->schoolClass->name }} · {{ $exam->subject->name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $exam->submitted_count }}</td>
                        <td class="px-6 py-4">
                            @if ($exam->isDeclared())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Declared</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Not Declared</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="viewResults({{ $exam->id }})" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                View Results
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            No exams to show results for yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Results Modal -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div x-show="$wire.showModal" x-transition.opacity @click="$wire.showModal = false" class="fixed inset-0 bg-black/40"></div>

        <div x-show="$wire.showModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 max-h-[85vh] overflow-y-auto">
            @php $exam = $exams->firstWhere('id', $viewingExamId); @endphp

            @if ($exam)
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-base font-semibold text-gray-900">{{ $exam->title }}</h2>
                    @if ($exam->isDeclared())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Declared</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mb-4">Total marks: {{ $exam->totalMarks() }} · Pass: {{ $exam->pass_percentage }}%</p>

                @if ($this->classPerformance)
                    <div class="grid grid-cols-4 gap-3 mb-4">
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500">Average</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $this->classPerformance['average'] }}%</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500">Highest</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $this->classPerformance['highest'] }}%</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500">Lowest</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $this->classPerformance['lowest'] }}%</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500">Pass Rate</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $this->classPerformance['pass_rate'] }}%</p>
                        </div>
                    </div>
                @endif

                <div class="space-y-2">
                    @forelse ($this->roster as $row)
                        <div wire:key="roster-{{ $row->student->id }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-200">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $row->student->user->name }}</p>
                                <p class="text-xs text-gray-500">Roll No. {{ $row->student->roll_number }}</p>
                            </div>
                            <div class="text-right flex items-center gap-3">
                                @if ($row->attempt?->status === 'submitted')
                                    <div>
                                        <p class="text-sm text-gray-900">{{ $row->attempt->obtained_marks }} / {{ $exam->totalMarks() }} ({{ $row->percentage }}%)</p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $row->passed ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                            {{ $row->passed ? 'Pass' : 'Fail' }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">Not attempted</span>
                                @endif
                                <a
                                    href="{{ route(request()->routeIs('admin.*') ? 'admin.marksheet' : 'teacher.marksheet', $row->student) }}"
                                    wire:navigate
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800 whitespace-nowrap"
                                >Marksheet</a>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-8">No students in this class yet.</p>
                    @endforelse
                </div>

                @error('declare')
                    <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="$wire.showModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">
                        Close
                    </button>
                    @if (! $exam->isDeclared())
                        <button
                            wire:click="declare"
                            wire:confirm="Declare results for this exam? Students will be able to see their marks."
                            class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700"
                        >Declare Results</button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
