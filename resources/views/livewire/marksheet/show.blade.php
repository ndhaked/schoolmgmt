<?php

use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Support\MarksheetBuilder;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public Student $student;

    public function mount(Student $student): void
    {
        $user = auth()->user();

        $authorized = match (true) {
            $user->hasRole('admin') => true,
            $user->hasRole('teacher') => TeacherAssignment::where('teacher_id', $user->teacher?->id)
                ->where('school_class_id', $student->school_class_id)
                ->exists(),
            $user->hasRole('parent') => $user->parentGuardian?->students->contains($student->id) ?? false,
            default => false,
        };

        abort_unless($authorized, 403);

        $this->student = $student;
    }

    public function with(): array
    {
        return MarksheetBuilder::build($this->student);
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Marksheet — {{ $student->user->name }}</h1>
    </x-slot>

    <x-marksheet-card :student="$student" :rows="$rows" :summary="$summary" />
</div>
