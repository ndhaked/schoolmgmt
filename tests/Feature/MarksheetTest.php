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
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarksheetTest extends TestCase
{
    use RefreshDatabase;

    private SchoolClass $class;
    private Student $student;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student', 'parent'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $this->admin->assignRole('admin');

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

        // Two declared exams in different subjects to exercise aggregation.
        $mathSubject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH10']);
        $mathSubject->classes()->attach($this->class->id);
        $this->declareExamFor($mathSubject, marks: 10, obtained: 9, passPercentage: 40); // 90% -> A+, Pass

        $scienceSubject = Subject::create(['name' => 'Science', 'code' => 'SCI10']);
        $scienceSubject->classes()->attach($this->class->id);
        $this->declareExamFor($scienceSubject, marks: 10, obtained: 3, passPercentage: 40); // 30% -> F, Fail
    }

    private function declareExamFor(Subject $subject, int $marks, int $obtained, int $passPercentage): Exam
    {
        $question = Question::create([
            'school_class_id' => $this->class->id, 'subject_id' => $subject->id,
            'created_by' => $this->admin->id, 'question_text' => 'Q', 'marks' => $marks,
        ]);
        $question->options()->createMany([
            ['option_text' => 'A', 'is_correct' => true],
            ['option_text' => 'B', 'is_correct' => false],
        ]);

        $exam = Exam::create([
            'title' => $subject->name . ' Exam',
            'school_class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'created_by' => $this->admin->id,
            'duration_minutes' => 30,
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->subHours(2),
            'status' => 'published',
            'pass_percentage' => $passPercentage,
            'results_declared_at' => now(),
        ]);
        $exam->questions()->attach($question->id);

        ExamAttempt::create([
            'exam_id' => $exam->id, 'student_id' => $this->student->id,
            'started_at' => now()->subHours(3), 'submitted_at' => now()->subHours(2),
            'status' => 'submitted', 'obtained_marks' => $obtained,
        ]);

        return $exam;
    }

    public function test_student_marksheet_aggregates_all_declared_exams(): void
    {
        $this->actingAs($this->student->user);

        Volt::test('student.results.marksheet')
            ->assertSee('Mathematics')
            ->assertSee('Science')
            ->assertSee('A+')
            ->assertSee('F')
            // Overall: (9+3)/(10+10) = 60% but one subject failed => overall FAIL
            ->assertSee('60%')
            ->assertSee('FAIL');
    }

    public function test_admin_can_view_any_students_marksheet(): void
    {
        $this->actingAs($this->admin);

        Volt::test('marksheet.show', ['student' => $this->student])
            ->assertSee('Aarav Sharma')
            ->assertSee('Mathematics');
    }

    public function test_teacher_can_view_marksheet_only_for_students_in_assigned_class(): void
    {
        $teacherUser = User::factory()->create(['email_verified_at' => now()]);
        $teacherUser->assignRole('teacher');
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'EMP-1']);

        $otherYear = AcademicYear::first();
        $otherClass = SchoolClass::create(['name' => 'Class 9', 'academic_year_id' => $otherYear->id]);

        // Not assigned to this teacher yet.
        $this->actingAs($teacherUser);
        $this->get(route('teacher.marksheet', $this->student))->assertForbidden();

        // Now assign the teacher to the student's class.
        $subject = Subject::first();
        TeacherAssignment::create([
            'teacher_id' => $teacher->id,
            'school_class_id' => $this->class->id,
            'subject_id' => $subject->id,
        ]);

        $this->get(route('teacher.marksheet', $this->student))->assertOk();
    }

    public function test_parent_can_only_view_marksheet_of_their_linked_child(): void
    {
        $parentUser = User::factory()->create(['email_verified_at' => now()]);
        $parentUser->assignRole('parent');
        $parentGuardian = ParentGuardian::create(['user_id' => $parentUser->id]);
        $parentGuardian->students()->attach($this->student->id);

        $this->actingAs($parentUser);
        $this->get(route('parent.marksheet', $this->student))->assertOk();

        $otherStudentUser = User::factory()->create(['email_verified_at' => now()]);
        $otherStudentUser->assignRole('student');
        $otherStudent = Student::create([
            'user_id' => $otherStudentUser->id,
            'school_class_id' => $this->class->id,
            'section_id' => Section::first()->id,
            'admission_no' => 'ADM-2',
            'roll_number' => '2',
        ]);

        $this->get(route('parent.marksheet', $otherStudent))->assertForbidden();
    }

    public function test_student_cannot_view_another_students_marksheet_route(): void
    {
        // Students only have the paramless self-route; the {student} route belongs to admin/teacher/parent prefixes.
        $this->actingAs($this->student->user);

        $this->get('/admin/students/1/marksheet')->assertForbidden();
    }
}
