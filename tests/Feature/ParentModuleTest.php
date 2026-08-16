<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ParentGuardian;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParentModuleTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'parent', 'student'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $this->class = SchoolClass::create(['name' => 'Class 10', 'academic_year_id' => $year->id]);
        $section = Section::create(['school_class_id' => $this->class->id, 'name' => 'A']);

        $studentUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Aarav Sharma']);
        $studentUser->assignRole('student');
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'school_class_id' => $this->class->id,
            'section_id' => $section->id,
            'admission_no' => 'ADM-1',
            'roll_number' => '1',
        ]);
    }

    public function test_admin_can_create_a_parent_and_link_a_child(): void
    {
        Volt::test('admin.parents.index')
            ->call('create')
            ->set('name', 'Rohit Sharma')
            ->set('email', 'rohit@school.test')
            ->set('selectedStudentIds', [(string) $this->student->id])
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'rohit@school.test')->firstOrFail();
        $this->assertTrue($user->hasRole('parent'));

        $parent = ParentGuardian::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($parent->students->contains($this->student));
    }

    public function test_deleting_a_parent_removes_login_account_and_links(): void
    {
        Volt::test('admin.parents.index')
            ->call('create')
            ->set('name', 'Rohit Sharma')
            ->set('email', 'rohit@school.test')
            ->set('selectedStudentIds', [(string) $this->student->id])
            ->call('save');

        $parent = ParentGuardian::firstOrFail();
        $userId = $parent->user_id;

        Volt::test('admin.parents.index')->call('delete', $parent->id);

        $this->assertDatabaseMissing('parent_guardians', ['id' => $parent->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('parent_student', ['parent_guardian_id' => $parent->id]);
    }

    public function test_parent_sees_only_declared_results_for_their_linked_child(): void
    {
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH10']);
        $subject->classes()->attach($this->class->id);

        $adminUser = User::first();
        $question = Question::create([
            'school_class_id' => $this->class->id, 'subject_id' => $subject->id,
            'created_by' => $adminUser->id, 'question_text' => 'Q1', 'marks' => 10,
        ]);
        $question->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
        ]);

        $exam = Exam::create([
            'title' => 'Weekly Quiz',
            'school_class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'created_by' => $adminUser->id,
            'duration_minutes' => 30,
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
            'status' => 'published',
            'pass_percentage' => 40,
        ]);
        $exam->questions()->attach($question->id);

        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->student->id,
            'started_at' => now()->subHours(2), 'submitted_at' => now()->subHour(),
            'status' => 'submitted', 'obtained_marks' => 10,
        ]);

        $parentUser = User::factory()->create(['email_verified_at' => now()]);
        $parentUser->assignRole('parent');
        $parentGuardian = ParentGuardian::create(['user_id' => $parentUser->id]);
        $parentGuardian->students()->attach($this->student->id);

        $this->actingAs($parentUser);

        // Not declared yet
        Volt::test('parent.results.index')->assertDontSee('Weekly Quiz');

        // Declare it
        $this->actingAs($adminUser);
        Volt::test('results.index')->call('viewResults', $exam->id)->call('declare');

        $this->actingAs($parentUser);
        Volt::test('parent.results.index')
            ->assertSee('Aarav Sharma')
            ->assertSee('Weekly Quiz')
            ->assertSee('Pass');
    }

    public function test_parent_does_not_see_unrelated_students_results(): void
    {
        $otherStudentUser = User::factory()->create(['email_verified_at' => now(), 'name' => 'Someone Else']);
        $otherStudentUser->assignRole('student');
        Student::create([
            'user_id' => $otherStudentUser->id,
            'school_class_id' => $this->class->id,
            'section_id' => Section::first()->id,
            'admission_no' => 'ADM-2',
            'roll_number' => '2',
        ]);

        $parentUser = User::factory()->create(['email_verified_at' => now()]);
        $parentUser->assignRole('parent');
        $parentGuardian = ParentGuardian::create(['user_id' => $parentUser->id]);
        $parentGuardian->students()->attach($this->student->id); // only linked to $this->student, not otherStudentUser

        $this->actingAs($parentUser);

        Volt::test('parent.results.index')
            ->assertSee('Aarav Sharma')
            ->assertDontSee('Someone Else');
    }

    public function test_non_admin_cannot_access_parents_management_page(): void
    {
        auth()->logout();

        $this->get('/admin/parents')->assertRedirect('/login');
    }
}
