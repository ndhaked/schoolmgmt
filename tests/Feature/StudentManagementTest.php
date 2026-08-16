<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $this->class = SchoolClass::create(['name' => 'Class 10', 'academic_year_id' => $year->id]);
        $this->section = Section::create(['school_class_id' => $this->class->id, 'name' => 'A']);
    }

    public function test_admin_can_create_a_student_with_login_account(): void
    {
        Volt::test('admin.students.index')
            ->call('create')
            ->set('name', 'Aarav Sharma')
            ->set('email', 'aarav@school.test')
            ->set('schoolClassId', $this->class->id)
            ->set('sectionId', $this->section->id)
            ->set('admissionNo', 'ADM-001')
            ->set('rollNumber', '1')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'aarav@school.test')->firstOrFail();
        $this->assertTrue($user->hasRole('student'));

        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'admission_no' => 'ADM-001',
            'roll_number' => '1',
        ]);
    }

    public function test_sections_dropdown_is_scoped_to_selected_class(): void
    {
        $otherClass = SchoolClass::create(['name' => 'Class 9', 'academic_year_id' => $this->class->academic_year_id]);
        Section::create(['school_class_id' => $otherClass->id, 'name' => 'B']);

        $component = Volt::test('admin.students.index')
            ->call('create')
            ->set('schoolClassId', $this->class->id);

        $sections = $component->get('sectionsForSelectedClass');

        $this->assertCount(1, $sections);
        $this->assertEquals('A', $sections->first()->name);
    }

    public function test_admin_can_edit_a_student(): void
    {
        Volt::test('admin.students.index')
            ->call('create')
            ->set('name', 'Aarav Sharma')
            ->set('email', 'aarav@school.test')
            ->set('schoolClassId', $this->class->id)
            ->set('sectionId', $this->section->id)
            ->set('admissionNo', 'ADM-001')
            ->set('rollNumber', '1')
            ->call('save');

        $student = Student::firstOrFail();

        Volt::test('admin.students.index')
            ->call('edit', $student->id)
            ->set('rollNumber', '42')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('students', ['id' => $student->id, 'roll_number' => '42']);
    }

    public function test_deleting_a_student_removes_their_login_account(): void
    {
        Volt::test('admin.students.index')
            ->call('create')
            ->set('name', 'Aarav Sharma')
            ->set('email', 'aarav@school.test')
            ->set('schoolClassId', $this->class->id)
            ->set('sectionId', $this->section->id)
            ->set('admissionNo', 'ADM-001')
            ->set('rollNumber', '1')
            ->call('save');

        $student = Student::firstOrFail();
        $userId = $student->user_id;

        Volt::test('admin.students.index')->call('delete', $student->id);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_admission_number_must_be_unique(): void
    {
        Volt::test('admin.students.index')
            ->call('create')
            ->set('name', 'Aarav Sharma')
            ->set('email', 'aarav@school.test')
            ->set('schoolClassId', $this->class->id)
            ->set('sectionId', $this->section->id)
            ->set('admissionNo', 'ADM-001')
            ->set('rollNumber', '1')
            ->call('save');

        Volt::test('admin.students.index')
            ->call('create')
            ->set('name', 'Second Student')
            ->set('email', 'second@school.test')
            ->set('schoolClassId', $this->class->id)
            ->set('sectionId', $this->section->id)
            ->set('admissionNo', 'ADM-001')
            ->set('rollNumber', '2')
            ->call('save')
            ->assertHasErrors('admissionNo');
    }

    public function test_teacher_cannot_access_students_page(): void
    {
        $teacher = User::factory()->create(['email_verified_at' => now()]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->get('/admin/students')
            ->assertForbidden();
    }
}
