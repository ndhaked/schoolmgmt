<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $year = AcademicYear::updateOrCreate(
            ['name' => '2026-2027'],
            ['start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_current' => true]
        );

        $class10 = SchoolClass::updateOrCreate(
            ['academic_year_id' => $year->id, 'name' => 'Class 10'],
            []
        );
        $sectionA = Section::updateOrCreate(
            ['school_class_id' => $class10->id, 'name' => 'A'],
            []
        );

        $maths = Subject::updateOrCreate(['code' => 'MATH10'], ['name' => 'Mathematics']);
        $science = Subject::updateOrCreate(['code' => 'SCI10'], ['name' => 'Science']);
        $maths->classes()->syncWithoutDetaching([$class10->id]);
        $science->classes()->syncWithoutDetaching([$class10->id]);

        // --- Teacher ---
        $teacherUser = User::updateOrCreate(
            ['email' => 'teacher@school.test'],
            ['name' => 'Priya Verma', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $teacherUser->assignRole('teacher');
        $teacher = Teacher::updateOrCreate(
            ['user_id' => $teacherUser->id],
            ['employee_no' => 'EMP-DEMO-1']
        );
        TeacherAssignment::firstOrCreate([
            'teacher_id' => $teacher->id, 'school_class_id' => $class10->id, 'subject_id' => $maths->id,
        ]);
        TeacherAssignment::firstOrCreate([
            'teacher_id' => $teacher->id, 'school_class_id' => $class10->id, 'subject_id' => $science->id,
        ]);

        // --- Students ---
        $students = [];
        foreach ([
            ['name' => 'Aarav Sharma', 'email' => 'student1@school.test', 'roll' => '1', 'phone' => '9876500001'],
            ['name' => 'Diya Patel', 'email' => 'student2@school.test', 'roll' => '2', 'phone' => '9876500002'],
            ['name' => 'Kabir Singh', 'email' => 'student3@school.test', 'roll' => '3', 'phone' => '9876500003'],
        ] as $i => $data) {
            $studentUser = User::updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
            $studentUser->assignRole('student');

            $students[] = Student::updateOrCreate(
                ['admission_no' => 'ADM-DEMO-' . ($i + 1)],
                [
                    'user_id' => $studentUser->id,
                    'school_class_id' => $class10->id,
                    'section_id' => $sectionA->id,
                    'roll_number' => $data['roll'],
                    'guardian_phone' => $data['phone'],
                ]
            );
        }

        // --- Parent linked to the first student ---
        $parentUser = User::updateOrCreate(
            ['email' => 'parent@school.test'],
            ['name' => 'Rohit Sharma', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $parentUser->assignRole('parent');
        $parentGuardian = ParentGuardian::updateOrCreate(['user_id' => $parentUser->id], ['phone' => '9876512345']);
        $parentGuardian->students()->syncWithoutDetaching([$students[0]->id]);

        // --- Question Bank: Mathematics ---
        $mathQuestions = [
            ['text' => 'What is 7 + 5?', 'options' => ['11', '12', '13', '10'], 'correct' => 1],
            ['text' => 'What is 9 x 6?', 'options' => ['52', '56', '54', '58'], 'correct' => 2],
            ['text' => 'What is the square root of 81?', 'options' => ['8', '9', '7', '10'], 'correct' => 1],
            ['text' => 'What is 15% of 200?', 'options' => ['20', '25', '30', '35'], 'correct' => 2],
            ['text' => 'Solve: 12 / 4 + 3', 'options' => ['6', '5', '9', '3'], 'correct' => 0],
        ];
        $mathQuestionModels = $this->seedQuestions($mathQuestions, $class10, $maths, $teacherUser);

        // --- Question Bank: Science ---
        $scienceQuestions = [
            ['text' => 'What is the chemical symbol for water?', 'options' => ['O2', 'H2O', 'CO2', 'NaCl'], 'correct' => 1],
            ['text' => 'Which planet is known as the Red Planet?', 'options' => ['Venus', 'Jupiter', 'Mars', 'Saturn'], 'correct' => 2],
            ['text' => 'What gas do plants absorb from the atmosphere?', 'options' => ['Oxygen', 'Nitrogen', 'Carbon Dioxide', 'Hydrogen'], 'correct' => 2],
        ];
        $scienceQuestionModels = $this->seedQuestions($scienceQuestions, $class10, $science, $teacherUser);

        // --- Exam 1: LIVE right now — log in as a student and take it ---
        $liveExam = Exam::updateOrCreate(
            ['title' => 'Live Demo Quiz — Mathematics'],
            [
                'school_class_id' => $class10->id,
                'subject_id' => $maths->id,
                'created_by' => $teacherUser->id,
                'duration_minutes' => 30,
                'starts_at' => now()->subMinutes(5),
                'ends_at' => now()->addHours(3),
                'pass_percentage' => 40,
                'status' => 'published',
            ]
        );
        $liveExam->questions()->syncWithoutDetaching(collect($mathQuestionModels)->pluck('id'));

        // --- Exam 2: already finished, submitted, and DECLARED — see results/marksheet immediately ---
        $pastExam = Exam::updateOrCreate(
            ['title' => 'Past Demo Quiz — Science'],
            [
                'school_class_id' => $class10->id,
                'subject_id' => $science->id,
                'created_by' => $teacherUser->id,
                'duration_minutes' => 20,
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->subDays(2)->addHour(),
                'pass_percentage' => 40,
                'status' => 'published',
                'results_declared_at' => now()->subDay(),
            ]
        );
        $pastExam->questions()->syncWithoutDetaching(collect($scienceQuestionModels)->pluck('id'));

        // Simulate student 1 having already taken and passed it, student 2 having failed it.
        $this->seedSubmittedAttempt($pastExam, $students[0], $scienceQuestionModels, correctCount: 3);
        $this->seedSubmittedAttempt($pastExam, $students[1], $scienceQuestionModels, correctCount: 1);

        $this->command->info('Demo data seeded. Login accounts (all password: "password"):');
        $this->command->info('  Teacher: teacher@school.test');
        $this->command->info('  Students: student1@school.test, student2@school.test, student3@school.test');
        $this->command->info('  Parent:  parent@school.test (linked to student1)');
    }

    private function seedQuestions(array $definitions, SchoolClass $class, Subject $subject, User $creator): array
    {
        $models = [];

        foreach ($definitions as $def) {
            $question = Question::updateOrCreate(
                [
                    'school_class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'question_text' => $def['text'],
                ],
                ['created_by' => $creator->id, 'marks' => 2, 'negative_marks' => 0]
            );

            $question->options()->delete();
            foreach ($def['options'] as $i => $optionText) {
                $question->options()->create([
                    'option_text' => $optionText,
                    'is_correct' => $i === $def['correct'],
                ]);
            }

            $models[] = $question;
        }

        return $models;
    }

    private function seedSubmittedAttempt(Exam $exam, Student $student, array $questions, int $correctCount): void
    {
        $attempt = ExamAttempt::updateOrCreate(
            ['exam_id' => $exam->id, 'student_id' => $student->id],
            [
                'started_at' => $exam->starts_at,
                'submitted_at' => $exam->starts_at->copy()->addMinutes(15),
                'status' => 'submitted',
            ]
        );

        $attempt->answers()->delete();
        $totalMarks = 0;

        foreach ($questions as $i => $question) {
            $isCorrect = $i < $correctCount;
            $option = $isCorrect
                ? $question->options()->where('is_correct', true)->first()
                : $question->options()->where('is_correct', false)->first();

            $marks = $isCorrect ? $question->marks : 0;
            $totalMarks += $marks;

            $attempt->answers()->create([
                'question_id' => $question->id,
                'selected_option_id' => $option->id,
                'is_correct' => $isCorrect,
                'marks_awarded' => $marks,
            ]);
        }

        $attempt->update(['obtained_marks' => $totalMarks]);
    }
}
