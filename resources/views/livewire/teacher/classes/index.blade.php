<?php

use App\Models\Student;
use App\Models\TeacherAssignment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public ?int $viewingAssignmentId = null;

    public function with(): array
    {
        return [
            'assignments' => TeacherAssignment::with(['schoolClass', 'subject'])
                ->where('teacher_id', auth()->user()->teacher?->id)
                ->get()
                ->map(function ($assignment) {
                    $assignment->studentCount = Student::where('school_class_id', $assignment->school_class_id)->count();

                    return $assignment;
                }),
        ];
    }

    #[Computed]
    public function roster()
    {
        if (! $this->viewingAssignmentId) {
            return collect();
        }

        $assignment = TeacherAssignment::findOrFail($this->viewingAssignmentId);
        abort_unless($assignment->teacher_id === auth()->user()->teacher?->id, 403);

        return Student::where('school_class_id', $assignment->school_class_id)
            ->with(['user', 'section'])
            ->orderBy('roll_number')
            ->get();
    }

    public function toggleRoster(int $assignmentId): void
    {
        $this->viewingAssignmentId = $this->viewingAssignmentId === $assignmentId ? null : $assignmentId;
        unset($this->roster);
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">My Classes</h1>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse ($assignments as $assignment)
            <div wire:key="assignment-{{ $assignment->id }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $assignment->schoolClass->name }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $assignment->subject->name }} · {{ $assignment->studentCount }} students</p>
                    </div>
                    <button wire:click="toggleRoster({{ $assignment->id }})" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        {{ $viewingAssignmentId === $assignment->id ? 'Hide Roster' : 'View Roster' }}
                    </button>
                </div>

                @if ($viewingAssignmentId === $assignment->id)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="space-y-1 max-h-64 overflow-y-auto">
                            @forelse ($this->roster as $student)
                                <div wire:key="student-{{ $student->id }}" class="flex items-center justify-between text-sm py-1.5">
                                    <span class="text-gray-900">{{ $student->user->name }}</span>
                                    <span class="text-gray-500 text-xs">Roll {{ $student->roll_number }} · Section {{ $student->section->name }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 text-center py-4">No students in this class yet.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center text-gray-500">
                You haven't been assigned to any classes yet. Contact the school admin.
            </div>
        @endforelse
    </div>
</div>
