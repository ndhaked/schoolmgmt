<?php

use App\Models\ParentGuardian;
use App\Models\Student;
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
    public string $phone = '';
    public string $occupation = '';
    public string $address = '';
    public array $selectedStudentIds = [];
    public string $studentSearch = '';

    public ?array $lastCreatedCredentials = null;

    public function with(): array
    {
        return [
            'parents' => ParentGuardian::with(['user', 'students.user', 'students.schoolClass'])
                ->join('users', 'users.id', '=', 'parent_guardians.user_id')
                ->orderBy('users.name')
                ->select('parent_guardians.*')
                ->get(),
        ];
    }

    #[Computed]
    public function filteredStudents()
    {
        $query = Student::with(['user', 'schoolClass']);

        if ($this->studentSearch !== '') {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%' . $this->studentSearch . '%'))
                ->orWhere('admission_no', 'like', '%' . $this->studentSearch . '%');
        }

        return $query->orderBy('id')->limit(50)->get();
    }

    public function updatedStudentSearch(): void
    {
        unset($this->filteredStudents);
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'occupation', 'address', 'selectedStudentIds', 'studentSearch']);
        $this->resetErrorBag();
        $this->lastCreatedCredentials = null;
        unset($this->filteredStudents);
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $parent = ParentGuardian::with(['user', 'students'])->findOrFail($id);

        $this->editingId = $parent->id;
        $this->name = $parent->user->name;
        $this->email = $parent->user->email;
        $this->phone = $parent->phone ?? '';
        $this->occupation = $parent->occupation ?? '';
        $this->address = $parent->address ?? '';
        $this->selectedStudentIds = $parent->students->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->studentSearch = '';
        unset($this->filteredStudents);
        $this->lastCreatedCredentials = null;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($this->editingId ? ParentGuardian::find($this->editingId)?->user_id : 'NULL'),
            'phone' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'selectedStudentIds' => 'array',
        ]);

        $generatedPassword = null;

        DB::transaction(function () use ($data, &$generatedPassword) {
            if ($this->editingId) {
                $parent = ParentGuardian::findOrFail($this->editingId);
                $parent->user->update(['name' => $data['name'], 'email' => $data['email']]);
            } else {
                $generatedPassword = Str::password(10);

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($generatedPassword),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole('parent');

                $parent = new ParentGuardian(['user_id' => $user->id]);
            }

            $parent->fill([
                'phone' => $data['phone'] ?: null,
                'occupation' => $data['occupation'] ?: null,
                'address' => $data['address'] ?: null,
            ])->save();

            $parent->students()->sync($data['selectedStudentIds']);
        });

        $this->showModal = false;

        if ($generatedPassword) {
            $this->lastCreatedCredentials = ['email' => $data['email'], 'password' => $generatedPassword];
        }
    }

    public function delete(int $id): void
    {
        $parent = ParentGuardian::findOrFail($id);
        $parent->user->delete();
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Parents</h1>
    </x-slot>

    @if ($lastCreatedCredentials)
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-start justify-between gap-4">
            <div class="text-sm text-emerald-800">
                <p class="font-medium">Parent account created.</p>
                <p class="mt-1">
                    Email: <span class="font-mono">{{ $lastCreatedCredentials['email'] }}</span>
                    &nbsp;·&nbsp;
                    Temporary password: <span class="font-mono">{{ $lastCreatedCredentials['password'] }}</span>
                </p>
                <p class="mt-1 text-xs text-emerald-700">Share these with the parent now — this password won't be shown again.</p>
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
            Add Parent
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Children</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($parents as $parent)
                    <tr wire:key="parent-{{ $parent->id }}">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $parent->user->name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $parent->user->email }}</td>
                        <td class="px-6 py-4 text-gray-600">
                            @forelse ($parent->students as $child)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 mr-1 mb-1">
                                    {{ $child->user->name }} ({{ $child->schoolClass->name }})
                                </span>
                            @empty
                                <span class="text-xs text-gray-400">No children linked</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
                            <button wire:click="edit({{ $parent->id }})" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                            <button
                                wire:click="delete({{ $parent->id }})"
                                wire:confirm="Delete this parent and their login account?"
                                class="text-red-600 hover:text-red-800 font-medium"
                            >Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                            No parents yet. Click "Add Parent" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div x-show="$wire.showModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4">
        <div x-show="$wire.showModal" x-transition.opacity @click="$wire.showModal = false" class="fixed inset-0 bg-black/40"></div>

        <div x-show="$wire.showModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-4">
                {{ $editingId ? 'Edit Parent' : 'Add Parent' }}
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" wire:model="phone" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                        <input type="text" wire:model="occupation" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea wire:model="address" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Children</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="studentSearch"
                        placeholder="Search by student name or admission no…"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm mb-2"
                    >
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-gray-100 rounded-lg p-2">
                        @forelse ($this->filteredStudents as $student)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="selectedStudentIds" value="{{ $student->id }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $student->user->name }} <span class="text-gray-400 text-xs">({{ $student->schoolClass->name }})</span>
                            </label>
                        @empty
                            <p class="text-xs text-gray-400 col-span-2 text-center py-4">No students found.</p>
                        @endforelse
                    </div>
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
</div>
