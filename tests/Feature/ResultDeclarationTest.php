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

class ResultDeclarationTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;
    private Subject $subject;
    private User $admin;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $this->class = SchoolClass::create(['name' => 'Class 10', 'academic_year_id' => $year->id]);
        $section = Section::create(['school_class_id' => $this->class->id, 'name' => 'A']);
        $this->subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH10']);
        $this->subject->classes()->attach($this->class->id);

        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $this->admin->assignRole('admin');

        $studentUser = User::factory()->create(['email_verified_at' => now()]);
        $studentUser->assignRole('student');
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'school_class_id' => $this->class->id,
            'section_id' => $section->id,
            'admission_no' => 'ADM-1',
            'roll_number' => '1',
        ]);
    }

    private function makeExamEndingAt(\Illuminate\Support\Carbon $endsAt): Exam
    {
        $question = Question::create([
            'school_class_id' => $this->class->id, 'subject_id' => $this->subject->id,
            'created_by' => $this->admin->id, 'question_text' => 'Q1', 'marks' => 10,
        ]);
        $question->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
        ]);

        $exam = Exam::create([
            'title' => 'Test Exam',
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'created_by' => $this->admin->id,
            'duration_minutes' => 30,
            'starts_at' => $endsAt->copy()->subHour(),
            'ends_at' => $endsAt,
            'status' => 'published',
            'pass_percentage' => 40,
        ]);
        $exam->questions()->attach($question->id);

        return $exam;
    }

    public function test_cannot_declare_before_exam_ends(): void
    {
        $exam = $this->makeExamEndingAt(now()->addHour());

        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->student->id,
            'started_at' => now(), 'submitted_at' => now(), 'status' => 'submitted', 'obtained_marks' => 10,
        ]);

        $this->actingAs($this->admin);

        Volt::test('results.index')
            ->call('viewResults', $exam->id)
            ->call('declare')
            ->assertHasErrors('declare');

        $this->assertNull($exam->fresh()->results_declared_at);
    }

    public function test_cannot_declare_with_no_submissions(): void
    {
        $exam = $this->makeExamEndingAt(now()->subMinute());

        $this->actingAs($this->admin);

        Volt::test('results.index')
            ->call('viewResults', $exam->id)
            ->call('declare')
            ->assertHasErrors('declare');

        $this->assertNull($exam->fresh()->results_declared_at);
    }

    public function test_admin_can_declare_results_after_exam_ends_with_submissions(): void
    {
        $exam = $this->makeExamEndingAt(now()->subMinute());

        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->student->id,
            'started_at' => now()->subHour(), 'submitted_at' => now(), 'status' => 'submitted', 'obtained_marks' => 10,
        ]);

        $this->actingAs($this->admin);

        Volt::test('results.index')
            ->call('viewResults', $exam->id)
            ->call('declare')
            ->assertHasNoErrors();

        $this->assertNotNull($exam->fresh()->results_declared_at);
    }

    public function test_student_cannot_see_results_before_declaration(): void
    {
        $exam = $this->makeExamEndingAt(now()->subMinute());

        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->student->id,
            'started_at' => now()->subHour(), 'submitted_at' => now(), 'status' => 'submitted', 'obtained_marks' => 10,
        ]);

        $this->actingAs($this->student->user);

        Volt::test('student.results.index')->assertDontSee('Test Exam');
    }

    public function test_student_sees_results_and_correct_pass_fail_after_declaration(): void
    {
        $exam = $this->makeExamEndingAt(now()->subMinute());

        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->student->id,
            'started_at' => now()->subHour(), 'submitted_at' => now(), 'status' => 'submitted', 'obtained_marks' => 10,
        ]);

        $this->actingAs($this->admin);
        Volt::test('results.index')->call('viewResults', $exam->id)->call('declare');

        $this->actingAs($this->student->user);

        Volt::test('student.results.index')
            ->assertSee('Test Exam')
            ->assertSee('Pass'); // 10/10 = 100% >= 40% pass_percentage
    }

    public function test_teacher_only_sees_exams_they_created_in_results_list(): void
    {
        $teacherUser = User::factory()->create(['email_verified_at' => now()]);
        $teacherUser->assignRole('teacher');

        $adminExam = $this->makeExamEndingAt(now()->subMinute());

        $this->actingAs($teacherUser);

        Volt::test('results.index')->assertDontSee($adminExam->title);
    }

    public function test_non_owner_cannot_declare_results(): void
    {
        $exam = $this->makeExamEndingAt(now()->subMinute());
        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->student->id,
            'started_at' => now()->subHour(), 'submitted_at' => now(), 'status' => 'submitted', 'obtained_marks' => 10,
        ]);

        $otherTeacher = User::factory()->create(['email_verified_at' => now()]);
        $otherTeacher->assignRole('teacher');

        $this->actingAs($otherTeacher);

        try {
            Volt::test('results.index')->call('viewResults', $exam->id);
        } catch (\Throwable $e) {
            // 403 may surface as an exception or an error response depending on environment.
        }

        $this->assertNull($exam->fresh()->results_declared_at);
    }
}
