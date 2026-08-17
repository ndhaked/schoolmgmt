<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_counts_instead_of_placeholders(): void
    {
        foreach (['admin', 'student', 'teacher'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $class = SchoolClass::create(['name' => 'Class 10', 'academic_year_id' => $year->id]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH10']);
        $subject->classes()->attach($class->id);

        // 2 students
        foreach (range(1, 2) as $i) {
            $studentUser = User::factory()->create(['email_verified_at' => now()]);
            $studentUser->assignRole('student');
            Student::create([
                'user_id' => $studentUser->id, 'school_class_id' => $class->id, 'section_id' => $section->id,
                'admission_no' => 'ADM-' . $i, 'roll_number' => (string) $i,
            ]);
        }

        // 1 teacher
        $teacherUser = User::factory()->create(['email_verified_at' => now()]);
        $teacherUser->assignRole('teacher');
        Teacher::create(['user_id' => $teacherUser->id, 'employee_no' => 'EMP-1']);

        // 1 active (published, not yet ended) exam, 1 declared exam
        Exam::create([
            'title' => 'Active Exam', 'school_class_id' => $class->id, 'subject_id' => $subject->id,
            'created_by' => $admin->id, 'duration_minutes' => 30,
            'starts_at' => now()->subMinutes(5), 'ends_at' => now()->addHour(), 'status' => 'published',
        ]);
        Exam::create([
            'title' => 'Declared Exam', 'school_class_id' => $class->id, 'subject_id' => $subject->id,
            'created_by' => $admin->id, 'duration_minutes' => 30,
            'starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHour(),
            'status' => 'published', 'results_declared_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('2', false) // total students
            ->assertSee('1', false) // total teachers / active exams / declared (all 1)
            ->assertDontSee('—');
    }
}
