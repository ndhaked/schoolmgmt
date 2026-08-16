<?php

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $employeeNo = '';
    public string $phone = '';
    public string $qualification = '';
    public string $address = '';

    public bool $showAssignmentModal = false;
    public ?int $assignmentTeacherId = null;
    public ?int $assignmentClassId = null;
    public ?int $assignmentSubjectId = null;

    public ?array $lastCreatedCredentials = null;

    public function with(): array
    {
        return [
            'teachers' => Teacher::with(['user', 'assignments.schoolClass', 'assignments.subject'])
                ->join('users', 'users.id', '=', 'teachers.user_id')
                ->orderBy('users.name')
                ->select('teachers.*')
                ->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ];
    }

    #[Computed]
    public function subjectsForSelectedClass()
    {
        if (! $this->assignmentClassId) {
            return collect();
        }

        return SchoolClass::findOrFail($this->assignmentClassId)->subjects()->orderBy('name')->get();
    }

    public function updatedAssignmentClassId(): void
    {
        $this->assignmentSubjectId = null;
        unset($this->subjectsForSelectedClass);
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'email', 'employeeNo', 'phone', 'qualification', 'address']);
        $this->resetErrorBag();
        $this->lastCreatedCredentials = null;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $teacher = Teacher::with('user')->findOrFail($id);

        $this->editingId = $teacher->id;
        $this->name = $teacher->user->name;
        $this->email = $teacher->user->email;
        $this->employeeNo = $teacher->employee_no;
        $this->phone = $teacher->phone ?? '';
        $this->qualification = $teacher->qualification ?? '';
        $this->address = $teacher->address ?? '';
        $this->lastCreatedCredentials = null;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($this->editingId ? Teacher::find($this->editingId)?->user_id : 'NULL'),
            'employeeNo' => 'required|string|max:255|unique:teachers,employee_no,' . $this->editingId,
            'phone' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'address' => 'nullable|string',
        ]);

        $generatedPassword = null;

        DB::transaction(function () use ($data, &$generatedPassword) {
            if ($this->editingId) {
                $teacher = Teacher::findOrFail($this->editingId);
                $teacher->user->update(['name' => $data['name'], 'email' => $data['email']]);
            } else {
                $generatedPassword = Str::password(10);

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($generatedPassword),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole('teacher');

                $teacher = new Teacher(['user_id' => $user->id]);
            }

            $teacher->fill([
                'employee_no' => $data['employeeNo'],
                'phone' => $data['phone'] ?: null,
                'qualification' => $data['qualification'] ?: null,
                'address' => $data['address'] ?: null,
            ])->save();
        });

        $this->showModal = false;

        if ($generatedPassword) {
            $this->lastCreatedCredentials = ['email' => $data['email'], 'password' => $generatedPassword];
        }
    }

    public function delete(int $id): void
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->user->delete();
    }

    public function addAssignment(int $teacherId): void
    {
        $this->reset(['assignmentClassId', 'assignmentSubjectId']);
        $this->resetErrorBag();
        $this->assignmentTeacherId = $teacherId;
        $this->showAssignmentModal = true;
    }

    public function saveAssignment(): void
    {
        $data = $this->validate([
            'assignmentClassId' => 'required|exists:school_classes,id',
            'assignmentSubjectId' => 'required|exists:subjects,id',
        ]);

        TeacherAssignment::firstOrCreate([
            'teacher_id' => $this->assignmentTeacherId,
            'school_class_id' => $data['assignmentClassId'],
            'subject_id' => $data['assignmentSubjectId'],
        ]);

        $this->showAssignmentModal = false;
    }

    public function removeAssignment(int $id): void
    {
        TeacherAssignment::findOrFail($id)->delete();
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Teachers</h1>
    </x-slot>

    @if ($lastCreatedCredentials)
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-start justify-between gap-4">
            <div class="text-sm text-emerald-800">
                <p class="font-medium">Teacher account created.</p>
                <p class="mt-1">
                    Email: <span class="font-mono">{{ $lastCreatedCredentials['email'] }}</span>
                    &nbsp;·&nbsp;
                    Temporary password: <span class="font-mono">{{ $lastCreatedCredentials['password'] }}</span>
                </p>
                <p class="mt-1 text-xs text-emerald-700">Share these with the teacher now — this password won't be shown again.</p>
            </div>
            <button wire:click="$set('lastCreatedCredentials', null)" class="text-emerald-600 hover:text-emerald-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <div class="flex justify-end mb-4">
        <button
            wire:click="create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Teacher
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse ($teachers as $teacher)
            <div wire:key="teacher-{{ $teacher->id }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $teacher->user->name }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $teacher->user->email }} · {{ $teacher->employee_no }}</p>
                        @if ($teacher->phone)
                            <p class="text-xs text-gray-500">{{ $teacher->phone }}</p>
                        @endif
                    </div>
                    <div class="space-x-3 text-sm shrink-0">
                        <button wire:click="edit({{ $teacher->id }})" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                        <button
                            wire:click="delete({{ $teacher->id }})"
                            wire:confirm="Delete this teacher and their login account?"
                            class="text-red-600 hover:text-red-800 font-medium"
                        >Delete</button>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Teaching Assignments</p>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($teacher->assignments as $assignment)
                            <span wire:key="assignment-{{ $assignment->id }}" class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">
                                {{ $assignment->schoolClass->name }} · {{ $assignment->subject->name }}
                                <button
                                    wire:click="removeAssignment({{ $assignment->id }})"
                                    wire:confirm="Remove this teaching assignment?"
                                    class="text-gray-400 hover:text-red-600"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        @empty
                            <span class="text-xs text-gray-400">No assignments yet.</span>
                        @endforelse

                        <button
                            wire:click="addAssignment({{ $teacher->id }})"
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full border border-dashed border-gray-300 text-xs font-medium text-gray-500 hover:border-indigo-400 hover:text-indigo-600"
                        >
                            + Add Assignment
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center text-gray-500">
                No teachers yet. Click "Add Teacher" to create one.
            </div>
        @endforelse
    </div>

    <!-- Teacher Modal -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div x-show="$wire.showModal" x-transition.opacity @click="$wire.showModal = false" class="fixed inset-0 bg-black/40"></div>

        <div x-show="$wire.showModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">
                {{ $editingId ? 'Edit Teacher' : 'Add Teacher' }}
            </h2>

            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" wire:model="name" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" wire:model="email" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employee No.</label>
                        <input type="text" wire:model="employeeNo" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('employeeNo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" wire:model="phone" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qualification</label>
                    <input type="text" wire:model="qualification" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea wire:model="address" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                </div>

                @if (! $editingId)
                    <p class="text-xs text-gray-500">A login account will be created automatically with a random password shown after saving.</p>
                @endif

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

    <!-- Assignment Modal -->
    <div x-show="$wire.showAssignmentModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div x-show="$wire.showAssignmentModal" x-transition.opacity @click="$wire.showAssignmentModal = false" class="fixed inset-0 bg-black/40"></div>

        <div x-show="$wire.showAssignmentModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Add Teaching Assignment</h2>

            <form wire:submit="saveAssignment" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                    <select wire:model.live="assignmentClassId" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Select class</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('assignmentClassId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <select wire:model="assignmentSubjectId" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Select subject</option>
                        @foreach ($this->subjectsForSelectedClass as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    @error('assignmentSubjectId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @if ($assignmentClassId && $this->subjectsForSelectedClass->isEmpty())
                        <p class="mt-1 text-xs text-gray-500">This class has no subjects assigned yet — add some under Subjects first.</p>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="$wire.showAssignmentModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">
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
