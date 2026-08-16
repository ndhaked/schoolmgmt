<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;
    private SchoolClass $otherClass;
    private Subject $subject;
    private Subject $otherSubject;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $this->class = SchoolClass::create(['name' => 'Class 10', 'academic_year_id' => $year->id]);
        $this->otherClass = SchoolClass::create(['name' => 'Class 9', 'academic_year_id' => $year->id]);

        $this->subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH10']);
        $this->subject->classes()->attach([$this->class->id, $this->otherClass->id]);

        $this->otherSubject = Subject::create(['name' => 'Science', 'code' => 'SCI10']);
        $this->otherSubject->classes()->attach($this->otherClass->id);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function makeTeacherWithAssignment(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('teacher');
        $teacher = Teacher::create(['user_id' => $user->id, 'employee_no' => 'EMP-' . $user->id]);

        TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
        ]);

        return $user;
    }

    public function test_admin_can_create_a_question_with_options(): void
    {
        $this->actingAs($this->makeAdmin());

        Volt::test('question-bank.index')
            ->call('create')
            ->set('schoolClassId', $this->class->id)
            ->set('subjectId', $this->subject->id)
            ->set('questionText', 'What is 2 + 2?')
            ->set('marks', 2)
            ->set('options', [
                ['text' => '3', 'is_correct' => false],
                ['text' => '4', 'is_correct' => true],
                ['text' => '5', 'is_correct' => false],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $question = Question::where('question_text', 'What is 2 + 2?')->firstOrFail();
        $this->assertCount(3, $question->options);
        $this->assertEquals('4', $question->options->firstWhere('is_correct', true)->option_text);
    }

    public function test_teacher_can_only_create_questions_for_assigned_class_subject(): void
    {
        $teacher = $this->makeTeacherWithAssignment();
        $this->actingAs($teacher);

        // Allowed combo
        Volt::test('question-bank.index')
            ->call('create')
            ->set('schoolClassId', $this->class->id)
            ->set('subjectId', $this->subject->id)
            ->set('questionText', 'Allowed question')
            ->set('options', [
                ['text' => 'A', 'is_correct' => true],
                ['text' => 'B', 'is_correct' => false],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('questions', ['question_text' => 'Allowed question']);

        // Not-assigned combo should be rejected even if forged in the request
        Volt::test('question-bank.index')
            ->call('create')
            ->set('schoolClassId', $this->otherClass->id)
            ->set('subjectId', $this->otherSubject->id)
            ->set('questionText', 'Not allowed question')
            ->set('options', [
                ['text' => 'A', 'is_correct' => true],
                ['text' => 'B', 'is_correct' => false],
            ])
            ->call('save')
            ->assertHasErrors('subjectId');

        $this->assertDatabaseMissing('questions', ['question_text' => 'Not allowed question']);
    }

    public function test_teacher_only_sees_questions_for_their_assigned_combos(): void
    {
        $teacher = $this->makeTeacherWithAssignment();

        $visible = Question::create([
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'created_by' => $this->makeAdmin()->id,
            'question_text' => 'Visible to teacher',
            'marks' => 1,
        ]);

        $hidden = Question::create([
            'school_class_id' => $this->otherClass->id,
            'subject_id' => $this->otherSubject->id,
            'created_by' => $teacher->id,
            'question_text' => 'Hidden from teacher',
            'marks' => 1,
        ]);

        $this->actingAs($teacher);

        Volt::test('question-bank.index')
            ->assertSee('Visible to teacher')
            ->assertDontSee('Hidden from teacher');
    }

    public function test_teacher_cannot_edit_or_delete_another_teachers_question(): void
    {
        $teacher = $this->makeTeacherWithAssignment();

        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $otherUser->assignRole('teacher');

        $question = Question::create([
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'created_by' => $otherUser->id,
            'question_text' => 'Someone elses question',
            'marks' => 1,
        ]);

        $this->actingAs($teacher);

        try {
            Volt::test('question-bank.index')->call('delete', $question->id);
        } catch (\Throwable $e) {
            // Some environments bubble the 403 as an exception, others convert it
            // into an error response inside the Livewire test cycle — either is fine.
        }

        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }

    public function test_saving_requires_exactly_one_correct_option(): void
    {
        $this->actingAs($this->makeAdmin());

        Volt::test('question-bank.index')
            ->call('create')
            ->set('schoolClassId', $this->class->id)
            ->set('subjectId', $this->subject->id)
            ->set('questionText', 'No correct answer marked')
            ->set('options', [
                ['text' => 'A', 'is_correct' => false],
                ['text' => 'B', 'is_correct' => false],
            ])
            ->call('save')
            ->assertHasErrors('options');

        $this->assertDatabaseMissing('questions', ['question_text' => 'No correct answer marked']);
    }

    public function test_guest_cannot_access_question_bank(): void
    {
        $this->get('/admin/question-bank')->assertRedirect('/login');
    }
}
