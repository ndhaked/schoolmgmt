<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamAttempt;
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

class StudentExamTakingTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;
    private Subject $subject;
    private Exam $exam;
    private Question $q1;
    private Question $q2;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'student'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $this->class = SchoolClass::create(['name' => 'Class 10', 'academic_year_id' => $year->id]);
        Section::create(['school_class_id' => $this->class->id, 'name' => 'A']);
        $this->subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH10']);
        $this->subject->classes()->attach($this->class->id);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->q1 = Question::create([
            'school_class_id' => $this->class->id, 'subject_id' => $this->subject->id,
            'created_by' => $admin->id, 'question_text' => '2 + 2?', 'marks' => 2, 'negative_marks' => 1,
        ]);
        $this->q1->options()->createMany([
            ['option_text' => '3', 'is_correct' => false],
            ['option_text' => '4', 'is_correct' => true],
        ]);

        $this->q2 = Question::create([
            'school_class_id' => $this->class->id, 'subject_id' => $this->subject->id,
            'created_by' => $admin->id, 'question_text' => '3 + 3?', 'marks' => 3,
        ]);
        $this->q2->options()->createMany([
            ['option_text' => '6', 'is_correct' => true],
            ['option_text' => '7', 'is_correct' => false],
        ]);

        $this->exam = Exam::create([
            'title' => 'Weekly Quiz',
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'created_by' => $admin->id,
            'duration_minutes' => 30,
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addHour(),
            'status' => 'published',
        ]);
        $this->exam->questions()->attach([$this->q1->id, $this->q2->id]);
    }

    private function makeStudent(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('student');

        Student::create([
            'user_id' => $user->id,
            'school_class_id' => $this->class->id,
            'section_id' => Section::first()->id,
            'admission_no' => 'ADM-' . $user->id,
            'roll_number' => '1',
        ]);

        return $user;
    }

    public function test_student_starting_exam_creates_an_attempt(): void
    {
        $student = $this->makeStudent();
        $this->actingAs($student);

        Volt::test('student.exams.take', ['exam' => $this->exam]);

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $this->exam->id,
            'student_id' => $student->student->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_selecting_an_option_autosaves_the_answer(): void
    {
        $student = $this->makeStudent();
        $this->actingAs($student);

        $correctOption = $this->q1->options()->where('is_correct', true)->first();

        Volt::test('student.exams.take', ['exam' => $this->exam])
            ->call('selectOption', $this->q1->id, $correctOption->id);

        $attempt = ExamAttempt::where('student_id', $student->student->id)->firstOrFail();

        $this->assertDatabaseHas('exam_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $this->q1->id,
            'selected_option_id' => $correctOption->id,
            'is_correct' => true,
        ]);
    }

    public function test_submitting_computes_correct_score_with_negative_marking(): void
    {
        $student = $this->makeStudent();
        $this->actingAs($student);

        $q1Wrong = $this->q1->options()->where('is_correct', false)->first();
        $q2Correct = $this->q2->options()->where('is_correct', true)->first();

        Volt::test('student.exams.take', ['exam' => $this->exam])
            ->call('selectOption', $this->q1->id, $q1Wrong->id)
            ->call('selectOption', $this->q2->id, $q2Correct->id)
            ->call('submitExam');

        $attempt = ExamAttempt::where('student_id', $student->student->id)->firstOrFail();

        // q1 wrong: -1 (negative_marks), q2 correct: +3 => net 2
        $this->assertEquals('submitted', $attempt->status);
        $this->assertEquals(2, (float) $attempt->obtained_marks);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_student_cannot_reopen_a_submitted_attempt_to_change_answers(): void
    {
        $student = $this->makeStudent();
        $this->actingAs($student);

        Volt::test('student.exams.take', ['exam' => $this->exam])->call('submitExam');

        $correctOption = $this->q1->options()->where('is_correct', true)->first();

        // Re-mounting the component after submission should show the finished state,
        // and attempting to answer again should not create/alter an answer.
        Volt::test('student.exams.take', ['exam' => $this->exam])
            ->assertSet('finished', true)
            ->call('selectOption', $this->q1->id, $correctOption->id);

        $this->assertDatabaseMissing('exam_answers', [
            'question_id' => $this->q1->id,
            'selected_option_id' => $correctOption->id,
        ]);
    }

    public function test_expired_attempt_is_auto_submitted_on_mount(): void
    {
        $student = $this->makeStudent();

        $studentModel = $student->student;
        ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $studentModel->id,
            'started_at' => now()->subMinutes(40), // duration is 30 minutes, so this has expired
            'status' => 'in_progress',
        ]);

        $this->actingAs($student);

        Volt::test('student.exams.take', ['exam' => $this->exam])
            ->assertSet('finished', true);

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $this->exam->id,
            'student_id' => $studentModel->id,
            'status' => 'submitted',
        ]);
    }

    public function test_student_from_a_different_class_cannot_access_the_exam(): void
    {
        $otherYear = AcademicYear::first();
        $otherClass = SchoolClass::create(['name' => 'Class 9', 'academic_year_id' => $otherYear->id]);
        $otherSection = Section::create(['school_class_id' => $otherClass->id, 'name' => 'A']);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('student');
        Student::create([
            'user_id' => $user->id,
            'school_class_id' => $otherClass->id,
            'section_id' => $otherSection->id,
            'admission_no' => 'ADM-OTHER',
            'roll_number' => '1',
        ]);

        $this->actingAs($user);

        $this->get(route('student.exams.take', $this->exam))->assertForbidden();
    }

    public function test_guest_cannot_access_exam_list(): void
    {
        $this->get('/student/exams')->assertRedirect('/login');
    }
}
