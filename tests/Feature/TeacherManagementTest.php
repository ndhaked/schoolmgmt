<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $this->class = SchoolClass::create(['name' => 'Class 10', 'academic_year_id' => $year->id]);
        $this->subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH10']);
        $this->subject->classes()->attach($this->class->id);
    }

    public function test_admin_can_create_a_teacher_with_login_account(): void
    {
        Volt::test('admin.teachers.index')
            ->call('create')
            ->set('name', 'Priya Verma')
            ->set('email', 'priya@school.test')
            ->set('employeeNo', 'EMP-001')
            ->set('phone', '9999999999')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'priya@school.test')->firstOrFail();
        $this->assertTrue($user->hasRole('teacher'));

        $this->assertDatabaseHas('teachers', [
            'user_id' => $user->id,
            'employee_no' => 'EMP-001',
        ]);
    }

    public function test_subjects_dropdown_is_scoped_to_selected_class(): void
    {
        $otherClass = SchoolClass::create(['name' => 'Class 9', 'academic_year_id' => $this->class->academic_year_id]);
        $otherSubject = Subject::create(['name' => 'Science', 'code' => 'SCI9']);
        $otherSubject->classes()->attach($otherClass->id);

        $teacher = $this->makeTeacher();

        $component = Volt::test('admin.teachers.index')
            ->call('addAssignment', $teacher->id)
            ->set('assignmentClassId', $this->class->id);

        $subjects = $component->get('subjectsForSelectedClass');

        $this->assertCount(1, $subjects);
        $this->assertEquals('Mathematics', $subjects->first()->name);
    }

    public function test_admin_can_add_and_remove_teaching_assignment(): void
    {
        $teacher = $this->makeTeacher();

        Volt::test('admin.teachers.index')
            ->call('addAssignment', $teacher->id)
            ->set('assignmentClassId', $this->class->id)
            ->set('assignmentSubjectId', $this->subject->id)
            ->call('saveAssignment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teacher_assignments', [
            'teacher_id' => $teacher->id,
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
        ]);

        $assignment = TeacherAssignment::where('teacher_id', $teacher->id)->firstOrFail();

        Volt::test('admin.teachers.index')->call('removeAssignment', $assignment->id);

        $this->assertDatabaseMissing('teacher_assignments', ['id' => $assignment->id]);
    }

    public function test_deleting_a_teacher_removes_their_login_account_and_assignments(): void
    {
        $teacher = $this->makeTeacher();

        TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
        ]);

        $userId = $teacher->user_id;

        Volt::test('admin.teachers.index')->call('delete', $teacher->id);

        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('teacher_assignments', ['teacher_id' => $teacher->id]);
    }

    public function test_employee_number_must_be_unique(): void
    {
        Volt::test('admin.teachers.index')
            ->call('create')
            ->set('name', 'Priya Verma')
            ->set('email', 'priya@school.test')
            ->set('employeeNo', 'EMP-001')
            ->call('save');

        Volt::test('admin.teachers.index')
            ->call('create')
            ->set('name', 'Second Teacher')
            ->set('email', 'second@school.test')
            ->set('employeeNo', 'EMP-001')
            ->call('save')
            ->assertHasErrors('employeeNo');
    }

    public function test_non_admin_cannot_access_teachers_page(): void
    {
        $teacher = $this->makeTeacher();

        $this->actingAs($teacher->user)
            ->get('/admin/teachers')
            ->assertForbidden();
    }

    private function makeTeacher(): Teacher
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('teacher');

        return Teacher::create([
            'user_id' => $user->id,
            'employee_no' => 'EMP-' . $user->id,
        ]);
    }
}
