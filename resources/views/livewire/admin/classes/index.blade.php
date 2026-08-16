<?php

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Section;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public bool $showClassModal = false;
    public ?int $editingClassId = null;
    public string $className = '';
    public ?int $academicYearId = null;

    public bool $showSectionModal = false;
    public ?int $sectionClassId = null;
    public ?int $editingSectionId = null;
    public string $sectionName = '';

    public function with(): array
    {
        return [
            'classes' => SchoolClass::with(['academicYear', 'sections'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
        ];
    }

    public function createClass(): void
    {
        $this->reset(['editingClassId', 'className', 'academicYearId']);
        $this->resetErrorBag();
        $this->academicYearId = AcademicYear::where('is_current', true)->value('id');
        $this->showClassModal = true;
    }

    public function editClass(int $id): void
    {
        $class = SchoolClass::findOrFail($id);

        $this->editingClassId = $class->id;
        $this->className = $class->name;
        $this->academicYearId = $class->academic_year_id;
        $this->showClassModal = true;
    }

    public function saveClass(): void
    {
        $data = $this->validate([
            'className' => 'required|string|max:255',
            'academicYearId' => 'required|exists:academic_years,id',
        ]);

        SchoolClass::updateOrCreate(
            ['id' => $this->editingClassId],
            ['name' => $data['className'], 'academic_year_id' => $data['academicYearId']]
        );

        $this->showClassModal = false;
    }

    public function deleteClass(int $id): void
    {
        SchoolClass::findOrFail($id)->delete();
    }

    public function createSection(int $classId): void
    {
        $this->reset(['editingSectionId', 'sectionName']);
        $this->resetErrorBag();
        $this->sectionClassId = $classId;
        $this->showSectionModal = true;
    }

    public function editSection(int $id): void
    {
        $section = Section::findOrFail($id);

        $this->editingSectionId = $section->id;
        $this->sectionClassId = $section->school_class_id;
        $this->sectionName = $section->name;
        $this->showSectionModal = true;
    }

    public function saveSection(): void
    {
        $data = $this->validate([
            'sectionName' => 'required|string|max:255',
        ]);

        Section::updateOrCreate(
            ['id' => $this->editingSectionId],
            ['name' => $data['sectionName'], 'school_class_id' => $this->sectionClassId]
        );

        $this->showSectionModal = false;
    }

    public function deleteSection(int $id): void
    {
        Section::findOrFail($id)->delete();
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Classes &amp; Sections</h1>
    </x-slot>

    <div class="flex justify-end mb-4">
        <button
            wire:click="createClass"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Class
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse ($classes as $class)
            <div wire:key="class-{{ $class->id }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $class->name }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $class->academicYear->name }}</p>
                    </div>
                    <div class="space-x-3 text-sm">
                        <button wire:click="editClass({{ $class->id }})" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                        <button
                            wire:click="deleteClass({{ $class->id }})"
                            wire:confirm="Delete this class and all its sections?"
                            class="text-red-600 hover:text-red-800 font-medium"
                        >Delete</button>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Sections</p>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($class->sections as $section)
                            <span wire:key="section-{{ $section->id }}" class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">
                                {{ $section->name }}
                                <button wire:click="editSection({{ $section->id }})" class="text-gray-400 hover:text-indigo-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                </button>
                                <button
                                    wire:click="deleteSection({{ $section->id }})"
                                    wire:confirm="Delete this section?"
                                    class="text-gray-400 hover:text-red-600"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        @empty
                            <span class="text-xs text-gray-400">No sections yet.</span>
                        @endforelse

                        <button
                            wire:click="createSection({{ $class->id }})"
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full border border-dashed border-gray-300 text-xs font-medium text-gray-500 hover:border-indigo-400 hover:text-indigo-600"
                        >
                            + Add Section
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center text-gray-500">
                No classes yet. Click "Add Class" to create one.
            </div>
        @endforelse
    </div>

    <!-- Class Modal -->
    <div x-show="$wire.showClassModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div x-show="$wire.showClassModal" x-transition.opacity @click="$wire.showClassModal = false" class="fixed inset-0 bg-black/40"></div>

        <div x-show="$wire.showClassModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">
                {{ $editingClassId ? 'Edit Class' : 'Add Class' }}
            </h2>

            <form wire:submit="saveClass" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class Name</label>
                    <input type="text" wire:model="className" placeholder="e.g. Class 10"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('className') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                    <select wire:model="academicYearId" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Select academic year</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                    @error('academicYearId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="$wire.showClassModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Section Modal -->
    <div x-show="$wire.showSectionModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div x-show="$wire.showSectionModal" x-transition.opacity @click="$wire.showSectionModal = false" class="fixed inset-0 bg-black/40"></div>

        <div x-show="$wire.showSectionModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">
                {{ $editingSectionId ? 'Edit Section' : 'Add Section' }}
            </h2>

            <form wire:submit="saveSection" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Section Name</label>
                    <input type="text" wire:model="sectionName" placeholder="e.g. A"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('sectionName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="$wire.showSectionModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">
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
