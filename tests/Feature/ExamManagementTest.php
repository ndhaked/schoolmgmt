<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
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

class ExamManagementTest extends TestCase
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

    private function makeQuestion(int $marks = 2): Question
    {
        $question = Question::create([
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'created_by' => $this->makeAdmin()->id,
            'question_text' => 'Sample question ' . uniqid(),
            'marks' => $marks,
        ]);

        $question->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
        ]);

        return $question;
    }

    public function test_admin_can_create_an_exam(): void
    {
        $this->actingAs($this->makeAdmin());

        Volt::test('exams.index')
            ->call('create')
            ->set('title', 'Mid-Term Maths')
            ->set('schoolClassId', $this->class->id)
            ->set('subjectId', $this->subject->id)
            ->set('startsAt', '2026-09-01T09:00')
            ->set('endsAt', '2026-09-01T10:00')
            ->set('durationMinutes', 60)
            ->set('passPercentage', 40)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('exams', ['title' => 'Mid-Term Maths', 'status' => 'draft']);
    }

    public function test_teacher_can_only_create_exam_for_assigned_class_subject(): void
    {
        $teacher = $this->makeTeacherWithAssignment();
        $this->actingAs($teacher);

        Volt::test('exams.index')
            ->call('create')
            ->set('title', 'Not allowed exam')
            ->set('schoolClassId', $this->otherClass->id)
            ->set('subjectId', $this->otherSubject->id)
            ->set('startsAt', '2026-09-01T09:00')
            ->set('endsAt', '2026-09-01T10:00')
            ->call('save')
            ->assertHasErrors('subjectId');

        $this->assertDatabaseMissing('exams', ['title' => 'Not allowed exam']);
    }

    public function test_exam_cannot_be_published_without_questions(): void
    {
        $this->actingAs($this->makeAdmin());

        $exam = Exam::create([
            'title' => 'Empty Exam',
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'created_by' => auth()->id(),
            'duration_minutes' => 30,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        Volt::test('exams.index')
            ->call('togglePublish', $exam->id)
            ->assertHasErrors('publish');

        $this->assertEquals('draft', $exam->fresh()->status);
    }

    public function test_admin_can_add_questions_to_exam_and_then_publish(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $question = $this->makeQuestion(marks: 5);

        $exam = Exam::create([
            'title' => 'Exam With Questions',
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'created_by' => $admin->id,
            'duration_minutes' => 30,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        Volt::test('exams.index')
            ->call('manageQuestions', $exam->id)
            ->call('toggleQuestion', $question->id);

        $this->assertDatabaseHas('exam_questions', ['exam_id' => $exam->id, 'question_id' => $question->id]);
        $this->assertEquals(5, $exam->fresh()->totalMarks());

        Volt::test('exams.index')
            ->call('togglePublish', $exam->id)
            ->assertHasNoErrors();

        $this->assertEquals('published', $exam->fresh()->status);
    }

    public function test_eligible_questions_are_scoped_to_exam_class_and_subject(): void
    {
        $this->actingAs($this->makeAdmin());

        $matchingQuestion = $this->makeQuestion();

        $unrelatedQuestion = Question::create([
            'school_class_id' => $this->otherClass->id,
            'subject_id' => $this->otherSubject->id,
            'created_by' => $this->makeAdmin()->id,
            'question_text' => 'Unrelated question',
            'marks' => 1,
        ]);

        $exam = Exam::create([
            'title' => 'Scoped Exam',
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'created_by' => auth()->id(),
            'duration_minutes' => 30,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ]);

        $component = Volt::test('exams.index')->call('manageQuestions', $exam->id);
        $eligibleIds = $component->get('eligibleQuestions')->pluck('id');

        $this->assertTrue($eligibleIds->contains($matchingQuestion->id));
        $this->assertFalse($eligibleIds->contains($unrelatedQuestion->id));
    }

    public function test_non_admin_cannot_access_exams_page(): void
    {
        $this->get('/admin/exams')->assertRedirect('/login');
    }
}
