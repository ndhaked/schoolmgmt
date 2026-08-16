<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->actingAs($admin);
    }

    public function test_admin_can_create_an_academic_year(): void
    {
        Volt::test('admin.academic-years.index')
            ->call('create')
            ->set('name', '2026-2027')
            ->set('start_date', '2026-04-01')
            ->set('end_date', '2027-03-31')
            ->set('is_current', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('academic_years', [
            'name' => '2026-2027',
            'is_current' => true,
        ]);
    }

    public function test_setting_a_new_current_year_unsets_the_previous_one(): void
    {
        $old = AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_current' => true,
        ]);

        Volt::test('admin.academic-years.index')
            ->call('create')
            ->set('name', '2026-2027')
            ->set('start_date', '2026-04-01')
            ->set('end_date', '2027-03-31')
            ->set('is_current', true)
            ->call('save');

        $this->assertFalse($old->fresh()->is_current);
        $this->assertDatabaseHas('academic_years', ['name' => '2026-2027', 'is_current' => true]);
    }

    public function test_admin_can_create_a_class_and_a_section(): void
    {
        $year = AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $component = Volt::test('admin.classes.index')
            ->call('createClass')
            ->set('className', 'Class 10')
            ->set('academicYearId', $year->id)
            ->call('saveClass')
            ->assertHasNoErrors();

        $class = SchoolClass::where('name', 'Class 10')->firstOrFail();

        $component
            ->call('createSection', $class->id)
            ->set('sectionName', 'A')
            ->call('saveSection')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sections', [
            'school_class_id' => $class->id,
            'name' => 'A',
        ]);
    }

    public function test_admin_can_create_a_subject_and_assign_it_to_a_class(): void
    {
        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $class = SchoolClass::create(['name' => 'Class 10', 'academic_year_id' => $year->id]);

        Volt::test('admin.subjects.index')
            ->call('create')
            ->set('name', 'Mathematics')
            ->set('code', 'MATH10')
            ->set('selectedClassIds', [(string) $class->id])
            ->call('save')
            ->assertHasNoErrors();

        $subject = Subject::where('code', 'MATH10')->firstOrFail();

        $this->assertTrue($subject->classes->contains($class));
    }

    public function test_deleting_a_class_cascades_to_its_sections(): void
    {
        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true,
        ]);
        $class = SchoolClass::create(['name' => 'Class 10', 'academic_year_id' => $year->id]);
        $section = Section::create(['school_class_id' => $class->id, 'name' => 'A']);

        Volt::test('admin.classes.index')->call('deleteClass', $class->id);

        $this->assertDatabaseMissing('school_classes', ['id' => $class->id]);
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }

    public function test_teacher_cannot_access_academic_years_page(): void
    {
        Role::firstOrCreate(['name' => 'teacher']);
        $teacher = User::factory()->create(['email_verified_at' => now()]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->get('/admin/academic-years')
            ->assertForbidden();
    }
}
