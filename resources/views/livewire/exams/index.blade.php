<?php

use App\Models\Exam;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $title = '';
    public ?int $schoolClassId = null;
    public ?int $subjectId = null;
    public int $durationMinutes = 60;
    public string $startsAt = '';
    public string $endsAt = '';
    public int $passPercentage = 40;

    public bool $showQuestionsModal = false;
    public ?int $questionsExamId = null;
    public array $selectedQuestionIds = [];

    public function with(): array
    {
        $query = Exam::with(['schoolClass', 'subject', 'creator', 'questions']);

        if (! auth()->user()->hasRole('admin')) {
            $query->whereIn('school_class_id', $this->assignedClassIds())
                ->whereIn('subject_id', $this->assignedSubjectIds());
        }

        return [
            'exams' => $query->latest()->get(),
            'availableClasses' => $this->availableClasses(),
        ];
    }

    #[Computed]
    public function subjectsForFormClass()
    {
        return $this->subjectsForClass($this->schoolClassId);
    }

    #[Computed]
    public function eligibleQuestions()
    {
        if (! $this->questionsExamId) {
            return collect();
        }

        $exam = Exam::findOrFail($this->questionsExamId);

        return Question::where('school_class_id', $exam->school_class_id)
            ->where('subject_id', $exam->subject_id)
            ->with('options')
            ->orderBy('id')
            ->get();
    }

    public function updatedSchoolClassId(): void
    {
        $this->subjectId = null;
        unset($this->subjectsForFormClass);
    }

    public function create(): void
    {
        $this->reset([
            'editingId', 'title', 'schoolClassId', 'subjectId',
            'durationMinutes', 'startsAt', 'endsAt', 'passPercentage',
        ]);
        $this->durationMinutes = 60;
        $this->passPercentage = 40;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $exam = Exam::findOrFail($id);
        $this->authorizeManage($exam);

        $this->editingId = $exam->id;
        $this->title = $exam->title;
        $this->schoolClassId = $exam->school_class_id;
        $this->subjectId = $exam->subject_id;
        $this->durationMinutes = $exam->duration_minutes;
        $this->startsAt = $exam->starts_at->format('Y-m-d\TH:i');
        $this->endsAt = $exam->ends_at->format('Y-m-d\TH:i');
        $this->passPercentage = $exam->pass_percentage;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'schoolClassId' => 'required|exists:school_classes,id',
            'subjectId' => 'required|exists:subjects,id',
            'durationMinutes' => 'required|integer|min:5',
            'startsAt' => 'required|date',
            'endsAt' => 'required|date|after:startsAt',
            'passPercentage' => 'required|integer|min:1|max:100',
        ]);

        if (! auth()->user()->hasRole('admin') && ! $this->isAssignedCombo($this->schoolClassId, $this->subjectId)) {
            $this->addError('subjectId', 'You are not assigned to teach this class and subject.');
            return;
        }

        Exam::updateOrCreate(
            ['id' => $this->editingId],
            [
                'title' => $data['title'],
                'school_class_id' => $data['schoolClassId'],
                'subject_id' => $data['subjectId'],
                'created_by' => $this->editingId ? Exam::find($this->editingId)->created_by : auth()->id(),
                'duration_minutes' => $data['durationMinutes'],
                'starts_at' => $data['startsAt'],
                'ends_at' => $data['endsAt'],
                'pass_percentage' => $data['passPercentage'],
            ]
        );

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        $exam = Exam::findOrFail($id);
        $this->authorizeManage($exam);
        $exam->delete();
    }

    public function togglePublish(int $id): void
    {
        $exam = Exam::with('questions')->findOrFail($id);
        $this->authorizeManage($exam);

        if ($exam->status === 'draft') {
            if ($exam->questions->isEmpty()) {
                $this->addError('publish', 'Add at least one question before publishing.');
                return;
            }
            $exam->update(['status' => 'published']);
        } else {
            $exam->update(['status' => 'draft']);
        }
    }

    public function manageQuestions(int $examId): void
    {
        $exam = Exam::findOrFail($examId);
        $this->authorizeManage($exam);

        $this->questionsExamId = $examId;
        $this->selectedQuestionIds = $exam->questions->pluck('id')->all();
        unset($this->eligibleQuestions);
        $this->showQuestionsModal = true;
    }

    public function toggleQuestion(int $questionId): void
    {
        $exam = Exam::findOrFail($this->questionsExamId);
        $this->authorizeManage($exam);

        if (in_array($questionId, $this->selectedQuestionIds)) {
            $exam->questions()->detach($questionId);
            $this->selectedQuestionIds = array_values(array_diff($this->selectedQuestionIds, [$questionId]));
        } else {
            $exam->questions()->attach($questionId);
            $this->selectedQuestionIds[] = $questionId;
        }
    }

    private function authorizeManage(Exam $exam): void
    {
        abort_unless(
            auth()->user()->hasRole('admin') || $exam->created_by === auth()->id(),
            403
        );
    }

    private function availableClasses()
    {
        if (auth()->user()->hasRole('admin')) {
            return SchoolClass::orderBy('name')->get();
        }

        return SchoolClass::whereIn('id', $this->assignedClassIds())->orderBy('name')->get();
    }

    private function subjectsForClass(?int $classId)
    {
        if (! $classId) {
            return collect();
        }

        $subjects = SchoolClass::findOrFail($classId)->subjects()->orderBy('name')->get();

        if (auth()->user()->hasRole('admin')) {
            return $subjects;
        }

        $assignedSubjectIds = TeacherAssignment::where('teacher_id', $this->teacherId())
            ->where('school_class_id', $classId)
            ->pluck('subject_id');

        return $subjects->whereIn('id', $assignedSubjectIds)->values();
    }

    private function isAssignedCombo(?int $classId, ?int $subjectId): bool
    {
        return TeacherAssignment::where('teacher_id', $this->teacherId())
            ->where('school_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    private function assignedClassIds()
    {
        return TeacherAssignment::where('teacher_id', $this->teacherId())->pluck('school_class_id');
    }

    private function assignedSubjectIds()
    {
        return TeacherAssignment::where('teacher_id', $this->teacherId())->pluck('subject_id');
    }

    private function teacherId(): ?int
    {
        return auth()->user()->teacher?->id;
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Exams</h1>
    </x-slot>

    <div class="flex justify-end mb-4">
        <button
            wire:click="create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Exam
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class / Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Schedule</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Questions</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($exams as $exam)
                    <tr wire:key="exam-{{ $exam->id }}">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $exam->title }}</td>
                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">{{ $exam->schoolClass->name }} · {{ $exam->subject->name }}</td>
                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                            {{ $exam->starts_at->format('d M Y, h:i A') }}
                            <span class="text-gray-400">({{ $exam->duration_minutes }} min)</span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $exam->questions->count() }} Q · {{ $exam->totalMarks() }} marks
                        </td>
                        <td class="px-6 py-4">
                            @if ($exam->status === 'published')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Published</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Draft</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
                            @if (auth()->user()->hasRole('admin') || $exam->created_by === auth()->id())
                                <button wire:click="manageQuestions({{ $exam->id }})" class="text-indigo-600 hover:text-indigo-800 font-medium">Questions</button>
                                <button wire:click="edit({{ $exam->id }})" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                                <button wire:click="togglePublish({{ $exam->id }})" class="text-amber-600 hover:text-amber-800 font-medium">
                                    {{ $exam->status === 'published' ? 'Unpublish' : 'Publish' }}
                                </button>
                                <button
                                    wire:click="delete({{ $exam->id }})"
                                    wire:confirm="Delete this exam?"
                                    class="text-red-600 hover:text-red-800 font-medium"
                                >Delete</button>
                            @else
                                <span class="text-gray-400 text-xs">Read only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            No exams yet. Click "Add Exam" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @error('publish')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror

    <!-- Exam Modal -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div x-show="$wire.showModal" x-transition.opacity @click="$wire.showModal = false" class="fixed inset-0 bg-black/40"></div>

        <div x-show="$wire.showModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">
                {{ $editingId ? 'Edit Exam' : 'Add Exam' }}
            </h2>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" wire:model="title" placeholder="e.g. Mid-Term Mathematics Exam"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                        <select wire:model.live="schoolClassId" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Select class</option>
                            @foreach ($availableClasses as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('schoolClassId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <select wire:model="subjectId" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Select subject</option>
                            @foreach ($this->subjectsForFormClass as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        @error('subjectId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Starts At</label>
                        <input type="datetime-local" wire:model="startsAt" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('startsAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ends At</label>
                        <input type="datetime-local" wire:model="endsAt" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('endsAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                        <input type="number" wire:model="durationMinutes" min="5" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('durationMinutes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pass Percentage</label>
                        <input type="number" wire:model="passPercentage" min="1" max="100" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('passPercentage') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="$wire.showModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Questions Modal -->
    <div x-show="$wire.showQuestionsModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div x-show="$wire.showQuestionsModal" x-transition.opacity @click="$wire.showQuestionsModal = false" class="fixed inset-0 bg-black/40"></div>

        <div x-show="$wire.showQuestionsModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Manage Questions</h2>
                <span class="text-xs font-medium text-gray-500">{{ count($selectedQuestionIds) }} selected</span>
            </div>

            <div class="space-y-2">
                @forelse ($this->eligibleQuestions as $question)
                    <label wire:key="eligible-{{ $question->id }}" class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:click="toggleQuestion({{ $question->id }})"
                            @checked(in_array($question->id, $selectedQuestionIds))
                            class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <div class="flex-1">
                            <p class="text-sm text-gray-900">{{ $question->question_text }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $question->options->count() }} options · {{ $question->marks }} marks
                            </p>
                        </div>
                    </label>
                @empty
                    <p class="text-sm text-gray-500 text-center py-8">
                        No questions available for this class/subject yet. Add some in the Question Bank first.
                    </p>
                @endforelse
            </div>

            <div class="flex justify-end pt-4">
                <button type="button" @click="$wire.showQuestionsModal = false" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>
