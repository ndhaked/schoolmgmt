<?php

use App\Models\SchoolClass;
use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $code = '';
    public array $selectedClassIds = [];

    public function with(): array
    {
        return [
            'subjects' => Subject::with('classes')->orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'code', 'selectedClassIds']);
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $subject = Subject::with('classes')->findOrFail($id);

        $this->editingId = $subject->id;
        $this->name = $subject->name;
        $this->code = $subject->code;
        $this->selectedClassIds = $subject->classes->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code,' . $this->editingId,
            'selectedClassIds' => 'array',
        ]);

        $subject = Subject::updateOrCreate(
            ['id' => $this->editingId],
            ['name' => $data['name'], 'code' => $data['code']]
        );

        $subject->classes()->sync($data['selectedClassIds']);

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        Subject::findOrFail($id)->delete();
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Subjects</h1>
    </x-slot>

    <div class="flex justify-end mb-4">
        <button
            wire:click="create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Subject
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Classes</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($subjects as $subject)
                    <tr wire:key="subject-{{ $subject->id }}">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $subject->name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $subject->code }}</td>
                        <td class="px-6 py-4 text-gray-600">
                            @forelse ($subject->classes as $class)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 mr-1">{{ $class->name }}</span>
                            @empty
                                <span class="text-xs text-gray-400">Not assigned</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button wire:click="edit({{ $subject->id }})" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                            <button
                                wire:click="delete({{ $subject->id }})"
                                wire:confirm="Delete this subject?"
                                class="text-red-600 hover:text-red-800 font-medium"
                            >Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                            No subjects yet. Click "Add Subject" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Modal -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div x-show="$wire.showModal" x-transition.opacity @click="$wire.showModal = false" class="fixed inset-0 bg-black/40"></div>

        <div x-show="$wire.showModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">
                {{ $editingId ? 'Edit Subject' : 'Add Subject' }}
            </h2>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject Name</label>
                    <input type="text" wire:model="name" placeholder="e.g. Mathematics"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject Code</label>
                    <input type="text" wire:model="code" placeholder="e.g. MATH10"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assign to Classes</label>
                    <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto">
                        @foreach ($classes as $class)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="selectedClassIds" value="{{ $class->id }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $class->name }}
                            </label>
                        @endforeach
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
</div>
