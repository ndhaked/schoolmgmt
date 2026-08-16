<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
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

class TeacherMyClassesTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sees_only_their_assigned_classes_with_student_counts(): void
    {
        foreach (['teacher', 'student'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $myClass = SchoolClass::create(['name' => 'Class 10', 'academic_year_id' => $year->id]);
        $section = Section::create(['school_class_id' => $myClass->id, 'name' => 'A']);
        $otherClass = SchoolClass::create(['name' => 'Class 9', 'academic_year_id' => $year->id]);

        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH10']);
        $subject->classes()->attach([$myClass->id, $otherClass->id]);

        $teacherUser = User::factory()->create(['email_verified_at' => now()]);
        $teacherUser->assignRole('teacher');
        $teacher = Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'EMP-1']);
        TeacherAssignment::create(['teacher_id' => $teacher->id, 'school_class_id' => $myClass->id, 'subject_id' => $subject->id]);
        // Note: no assignment to $otherClass.

        foreach (range(1, 3) as $i) {
            $studentUser = User::factory()->create(['email_verified_at' => now()]);
            $studentUser->assignRole('student');
            Student::create([
                'user_id' => $studentUser->id,
                'school_class_id' => $myClass->id,
                'section_id' => $section->id,
                'admission_no' => 'ADM-' . $i,
                'roll_number' => (string) $i,
            ]);
        }

        $this->actingAs($teacherUser);

        Volt::test('teacher.classes.index')
            ->assertSee('Class 10')
            ->assertDontSee('Class 9');

        $component = Volt::test('teacher.classes.index');
        $assignment = TeacherAssignment::firstOrFail();

        $component->call('toggleRoster', $assignment->id)
            ->assertSee('Roll 1')
            ->assertSee('Roll 2')
            ->assertSee('Roll 3');
    }

    public function test_non_teacher_cannot_access_my_classes_page(): void
    {
        $this->get('/teacher/classes')->assertRedirect('/login');
    }
}
