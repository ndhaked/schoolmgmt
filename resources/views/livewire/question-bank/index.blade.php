<?php

use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\TeacherAssignment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.panel')] class extends Component
{
    use WithPagination;

    public ?int $filterClassId = null;
    public ?int $filterSubjectId = null;
    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $schoolClassId = null;
    public ?int $subjectId = null;
    public string $questionText = '';
    public int $marks = 1;
    public float $negativeMarks = 0;
    public array $options = [
        ['text' => '', 'is_correct' => true],
        ['text' => '', 'is_correct' => false],
    ];

    public function with(): array
    {
        $query = Question::query()->with(['schoolClass', 'subject', 'creator', 'options']);

        if (! auth()->user()->hasRole('admin')) {
            $query->whereIn('school_class_id', $this->assignedClassIds())
                ->whereIn('subject_id', $this->assignedSubjectIds());
        }

        if ($this->filterClassId) {
            $query->where('school_class_id', $this->filterClassId);
        }

        if ($this->filterSubjectId) {
            $query->where('subject_id', $this->filterSubjectId);
        }

        if ($this->search !== '') {
            $query->where('question_text', 'like', '%' . $this->search . '%');
        }

        return [
            'questions' => $query->latest()->paginate(10),
            'availableClasses' => $this->availableClasses(),
        ];
    }

    #[Computed]
    public function subjectsForFilterClass()
    {
        return $this->subjectsForClass($this->filterClassId);
    }

    #[Computed]
    public function subjectsForFormClass()
    {
        return $this->subjectsForClass($this->schoolClassId);
    }

    public function updatedFilterClassId(): void
    {
        $this->filterSubjectId = null;
        unset($this->subjectsForFilterClass);
    }

    public function updatedSchoolClassId(): void
    {
        $this->subjectId = null;
        unset($this->subjectsForFormClass);
    }

    public function create(): void
    {
        $this->reset(['editingId', 'schoolClassId', 'subjectId', 'questionText', 'marks', 'negativeMarks']);
        $this->options = [
            ['text' => '', 'is_correct' => true],
            ['text' => '', 'is_correct' => false],
        ];
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $question = Question::with('options')->findOrFail($id);
        $this->authorizeManage($question);

        $this->editingId = $question->id;
        $this->schoolClassId = $question->school_class_id;
        $this->subjectId = $question->subject_id;
        $this->questionText = $question->question_text;
        $this->marks = $question->marks;
        $this->negativeMarks = (float) $question->negative_marks;
        $this->options = $question->options->map(fn ($o) => [
            'text' => $o->option_text,
            'is_correct' => $o->is_correct,
        ])->all();
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function addOption(): void
    {
        if (count($this->options) < 6) {
            $this->options[] = ['text' => '', 'is_correct' => false];
        }
    }

    public function removeOption(int $index): void
    {
        if (count($this->options) > 2) {
            unset($this->options[$index]);
            $this->options = array_values($this->options);

            if (! collect($this->options)->contains('is_correct', true)) {
                $this->options[0]['is_correct'] = true;
            }
        }
    }

    public function setCorrect(int $index): void
    {
        foreach ($this->options as $i => $option) {
            $this->options[$i]['is_correct'] = $i === $index;
        }
    }

    public function save(): void
    {
        $this->validate([
            'schoolClassId' => 'required|exists:school_classes,id',
            'subjectId' => 'required|exists:subjects,id',
            'questionText' => 'required|string',
            'marks' => 'required|integer|min:1',
            'negativeMarks' => 'required|numeric|min:0',
            'options' => 'array|min:2',
            'options.*.text' => 'required|string',
        ]);

        if (! auth()->user()->hasRole('admin') && ! $this->isAssignedCombo($this->schoolClassId, $this->subjectId)) {
            $this->addError('subjectId', 'You are not assigned to teach this class and subject.');
            return;
        }

        if (! collect($this->options)->contains('is_correct', true)) {
            $this->addError('options', 'Mark exactly one option as correct.');
            return;
        }

        DB::transaction(function () {
            $question = Question::updateOrCreate(
                ['id' => $this->editingId],
                [
                    'school_class_id' => $this->schoolClassId,
                    'subject_id' => $this->subjectId,
                    'created_by' => $this->editingId ? Question::find($this->editingId)->created_by : auth()->id(),
                    'question_text' => $this->questionText,
                    'marks' => $this->marks,
                    'negative_marks' => $this->negativeMarks,
                ]
            );

            $question->options()->delete();
            foreach ($this->options as $option) {
                $question->options()->create([
                    'option_text' => $option['text'],
                    'is_correct' => $option['is_correct'],
                ]);
            }
        });

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        $question = Question::findOrFail($id);
        $this->authorizeManage($question);
        $question->delete();
    }

    private function authorizeManage(Question $question): void
    {
        abort_unless(
            auth()->user()->hasRole('admin') || $question->created_by === auth()->id(),
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
        <h1 class="text-lg font-semibold text-gray-900">Question Bank</h1>
    </x-slot>

    <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
        <div class="flex flex-wrap gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Class</label>
                <select wire:model.live="filterClassId" class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All classes</option>
                    @foreach ($availableClasses as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Subject</label>
                <select wire:model.live="filterSubjectId" class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All subjects</option>
                    @foreach ($this->subjectsForFilterClass as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search question text…"
                    class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <button
            wire:click="create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Question
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Question</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class / Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Marks</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created By</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($questions as $question)
                    <tr wire:key="question-{{ $question->id }}">
                        <td class="px-6 py-4 text-gray-900 max-w-md">
                            <p class="line-clamp-2">{{ $question->question_text }}</p>
                        </td>
                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">{{ $question->schoolClass->name }} · {{ $question->subject->name }}</td>
                        <td class="px-6 py-4 text-gray-600">
                            +{{ $question->marks }}
                            @if ($question->negative_marks > 0)
                                <span class="text-red-500">/ -{{ $question->negative_marks }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap">{{ $question->creator->name }}</td>
                        <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
                            @if (auth()->user()->hasRole('admin') || $question->created_by === auth()->id())
                                <button wire:click="edit({{ $question->id }})" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                                <button
                                    wire:click="delete({{ $question->id }})"
                                    wire:confirm="Delete this question?"
                                    class="text-red-600 hover:text-red-800 font-medium"
                                >Delete</button>
                            @else
                                <span class="text-gray-400 text-xs">Read only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            No questions yet. Click "Add Question" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($questions->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $questions->links() }}
            </div>
        @endif
    </div>

    <!-- Modal -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div x-show="$wire.showModal" x-transition.opacity @click="$wire.showModal = false" class="fixed inset-0 bg-black/40"></div>

        <div x-show="$wire.showModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-4">
                {{ $editingId ? 'Edit Question' : 'Add Question' }}
            </h2>

            <form wire:submit="save" class="space-y-4">
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Question Text</label>
                    <textarea wire:model="questionText" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                    @error('questionText') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marks</label>
                        <input type="number" wire:model="marks" min="1" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('marks') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Negative Marks</label>
                        <input type="number" step="0.25" wire:model="negativeMarks" min="0" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('negativeMarks') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Options — select the correct one</label>
                        @if (count($options) < 6)
                            <button type="button" wire:click="addOption" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">+ Add option</button>
                        @endif
                    </div>

                    <div class="space-y-2">
                        @foreach ($options as $index => $option)
                            <div class="flex items-center gap-2" wire:key="option-{{ $index }}">
                                <button
                                    type="button"
                                    wire:click="setCorrect({{ $index }})"
                                    class="shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center {{ $option['is_correct'] ? 'border-emerald-500 bg-emerald-500' : 'border-gray-300' }}"
                                >
                                    @if ($option['is_correct'])
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                        </svg>
                                    @endif
                                </button>
                                <input type="text" wire:model="options.{{ $index }}.text" placeholder="Option {{ $index + 1 }}"
                                    class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @if (count($options) > 2)
                                    <button type="button" wire:click="removeOption({{ $index }})" class="text-gray-400 hover:text-red-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @error('options') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('options.*.text') <p class="mt-1 text-xs text-red-600">Every option needs text.</p> @enderror
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
</div>
