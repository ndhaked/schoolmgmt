<?php

use App\Models\SchoolClass;
use App\Models\Section;
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
    public ?int $schoolClassId = null;
    public ?int $sectionId = null;
    public string $admissionNo = '';
    public string $rollNumber = '';
    public string $dateOfBirth = '';
    public string $gender = '';
    public string $guardianName = '';
    public string $guardianPhone = '';
    public string $address = '';

    public ?array $lastCreatedCredentials = null;

    public function with(): array
    {
        return [
            'students' => Student::with(['user', 'schoolClass', 'section'])
                ->join('users', 'users.id', '=', 'students.user_id')
                ->orderBy('users.name')
                ->select('students.*')
                ->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ];
    }

    #[Computed]
    public function sectionsForSelectedClass()
    {
        if (! $this->schoolClassId) {
            return collect();
        }

        return Section::where('school_class_id', $this->schoolClassId)->orderBy('name')->get();
    }

    public function updatedSchoolClassId(): void
    {
        $this->sectionId = null;
        unset($this->sectionsForSelectedClass);
    }

    public function create(): void
    {
        $this->reset([
            'editingId', 'name', 'email', 'schoolClassId', 'sectionId', 'admissionNo',
            'rollNumber', 'dateOfBirth', 'gender', 'guardianName', 'guardianPhone', 'address',
        ]);
        $this->resetErrorBag();
        $this->lastCreatedCredentials = null;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $student = Student::with('user')->findOrFail($id);

        $this->editingId = $student->id;
        $this->name = $student->user->name;
        $this->email = $student->user->email;
        $this->schoolClassId = $student->school_class_id;
        $this->sectionId = $student->section_id;
        $this->admissionNo = $student->admission_no;
        $this->rollNumber = $student->roll_number;
        $this->dateOfBirth = optional($student->date_of_birth)->format('Y-m-d') ?? '';
        $this->gender = $student->gender ?? '';
        $this->guardianName = $student->guardian_name ?? '';
        $this->guardianPhone = $student->guardian_phone ?? '';
        $this->address = $student->address ?? '';
        $this->lastCreatedCredentials = null;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($this->editingId ? Student::find($this->editingId)?->user_id : 'NULL'),
            'schoolClassId' => 'required|exists:school_classes,id',
            'sectionId' => 'required|exists:sections,id',
            'admissionNo' => 'required|string|max:255|unique:students,admission_no,' . $this->editingId,
            'rollNumber' => 'required|string|max:255',
            'dateOfBirth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'guardianName' => 'nullable|string|max:255',
            'guardianPhone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
        ]);

        $generatedPassword = null;

        DB::transaction(function () use ($data, &$generatedPassword) {
            if ($this->editingId) {
                $student = Student::findOrFail($this->editingId);
                $student->user->update(['name' => $data['name'], 'email' => $data['email']]);
            } else {
                $generatedPassword = Str::password(10);

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($generatedPassword),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole('student');

                $student = new Student(['user_id' => $user->id]);
            }

            $student->fill([
                'school_class_id' => $data['schoolClassId'],
                'section_id' => $data['sectionId'],
                'admission_no' => $data['admissionNo'],
                'roll_number' => $data['rollNumber'],
                'date_of_birth' => $data['dateOfBirth'] ?: null,
                'gender' => $data['gender'] ?: null,
                'guardian_name' => $data['guardianName'] ?: null,
                'guardian_phone' => $data['guardianPhone'] ?: null,
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
        $student = Student::findOrFail($id);
        $student->user->delete();
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">Students</h1>
    </x-slot>

    @if ($lastCreatedCredentials)
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-start justify-between gap-4">
            <div class="text-sm text-emerald-800">
                <p class="font-medium">Student account created.</p>
                <p class="mt-1">
                    Email: <span class="font-mono">{{ $lastCreatedCredentials['email'] }}</span>
                    &nbsp;·&nbsp;
                    Temporary password: <span class="font-mono">{{ $lastCreatedCredentials['password'] }}</span>
                </p>
                <p class="mt-1 text-xs text-emerald-700">Share these with the student now — this password won't be shown again.</p>
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
            Add Student
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class / Section</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Roll No.</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Admission No.</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($students as $student)
                    <tr wire:key="student-{{ $student->id }}">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $student->user->name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $student->schoolClass->name }} - {{ $student->section->name }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $student->roll_number }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $student->admission_no }}</td>
                        <td class="px-6 py-4">
                            <a href="mailto:{{ $student->user->email }}" class="text-indigo-600 hover:text-indigo-800 hover:underline">{{ $student->user->email }}</a>
                        </td>
                        <td class="px-6 py-4">
                            @if ($student->guardian_phone)
                                <a href="tel:{{ $student->guardian_phone }}" class="text-indigo-600 hover:text-indigo-800 hover:underline whitespace-nowrap">{{ $student->guardian_phone }}</a>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
                            <button wire:click="edit({{ $student->id }})" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                            <button
                                wire:click="delete({{ $student->id }})"
                                wire:confirm="Delete this student and their login account?"
                                class="text-red-600 hover:text-red-800 font-medium"
                            >Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                            No students yet. Click "Add Student" to create one.
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

        <div x-show="$wire.showModal" x-transition class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-4">
                {{ $editingId ? 'Edit Student' : 'Add Student' }}
            </h2>

            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                        <select wire:model.live="schoolClassId" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Select class</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('schoolClassId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                        <select wire:model="sectionId" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Select section</option>
                            @foreach ($this->sectionsForSelectedClass as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                        @error('sectionId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Admission No.</label>
                        <input type="text" wire:model="admissionNo" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('admissionNo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Roll Number</label>
                        <input type="text" wire:model="rollNumber" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('rollNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                        <input type="date" wire:model="dateOfBirth" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        @error('dateOfBirth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select wire:model="gender" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                        @error('gender') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Name</label>
                        <input type="text" wire:model="guardianName" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Phone</label>
                        <input type="text" wire:model="guardianPhone" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>
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
</div>
