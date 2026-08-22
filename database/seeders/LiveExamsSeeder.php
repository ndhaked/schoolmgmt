<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class LiveExamsSeeder extends Seeder
{
    /**
     * Generates a batch of always-open demo exams (long window, currently live)
     * so a sales demo can show a fresh "take exam right now" flow to a new
     * school without needing to reset data between demos.
     */
    public function run(): void
    {
        $class = SchoolClass::where('name', 'Class 10')->firstOrFail();
        $creator = User::where('email', 'teacher@school.test')->first()
            ?? User::where('email', 'admin@school.test')->firstOrFail();

        $english = Subject::firstOrCreate(['code' => 'ENG10'], ['name' => 'English']);
        $english->classes()->syncWithoutDetaching([$class->id]);

        $this->seedEnglishQuestions($class, $english, $creator);

        $subjects = Subject::whereIn('code', ['MATH10', 'SCI10', 'ENG10'])->get()->keyBy('code');

        $titles = [
            ['title' => 'Weekly Test 1 - Mathematics', 'subject' => 'MATH10', 'duration' => 30],
            ['title' => 'Weekly Test 2 - Mathematics', 'subject' => 'MATH10', 'duration' => 30],
            ['title' => 'Weekly Test 3 - Mathematics', 'subject' => 'MATH10', 'duration' => 45],
            ['title' => 'Unit Test 1 - Mathematics', 'subject' => 'MATH10', 'duration' => 45],
            ['title' => 'Unit Test 2 - Mathematics', 'subject' => 'MATH10', 'duration' => 45],
            ['title' => 'Monthly Assessment - Mathematics', 'subject' => 'MATH10', 'duration' => 60],
            ['title' => 'Practice Quiz 1 - Mathematics', 'subject' => 'MATH10', 'duration' => 20],
            ['title' => 'Practice Quiz 2 - Mathematics', 'subject' => 'MATH10', 'duration' => 20],
            ['title' => 'Mock Test - Mathematics', 'subject' => 'MATH10', 'duration' => 60],
            ['title' => 'Weekly Test 1 - Science', 'subject' => 'SCI10', 'duration' => 30],
            ['title' => 'Weekly Test 2 - Science', 'subject' => 'SCI10', 'duration' => 30],
            ['title' => 'Weekly Test 3 - Science', 'subject' => 'SCI10', 'duration' => 45],
            ['title' => 'Unit Test 1 - Science', 'subject' => 'SCI10', 'duration' => 45],
            ['title' => 'Unit Test 2 - Science', 'subject' => 'SCI10', 'duration' => 45],
            ['title' => 'Monthly Assessment - Science', 'subject' => 'SCI10', 'duration' => 60],
            ['title' => 'Practice Quiz 1 - Science', 'subject' => 'SCI10', 'duration' => 20],
            ['title' => 'Practice Quiz 2 - Science', 'subject' => 'SCI10', 'duration' => 20],
            ['title' => 'Mock Test - Science', 'subject' => 'SCI10', 'duration' => 60],
            ['title' => 'Weekly Test 1 - English', 'subject' => 'ENG10', 'duration' => 30],
            ['title' => 'Weekly Test 2 - English', 'subject' => 'ENG10', 'duration' => 30],
            ['title' => 'Weekly Test 3 - English', 'subject' => 'ENG10', 'duration' => 45],
            ['title' => 'Unit Test 1 - English', 'subject' => 'ENG10', 'duration' => 45],
            ['title' => 'Monthly Assessment - English', 'subject' => 'ENG10', 'duration' => 60],
            ['title' => 'Practice Quiz 1 - English', 'subject' => 'ENG10', 'duration' => 20],
            ['title' => 'Mock Test - English', 'subject' => 'ENG10', 'duration' => 60],
        ];

        foreach ($titles as $row) {
            $subject = $subjects[$row['subject']];

            $exam = Exam::updateOrCreate(
                ['title' => $row['title']],
                [
                    'school_class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'created_by' => $creator->id,
                    'duration_minutes' => $row['duration'],
                    'starts_at' => now()->subMinutes(10),
                    'ends_at' => now()->addDays(90),
                    'pass_percentage' => 40,
                    'status' => 'published',
                ]
            );

            $questionIds = Question::where('school_class_id', $class->id)
                ->where('subject_id', $subject->id)
                ->pluck('id');

            $exam->questions()->syncWithoutDetaching($questionIds);
        }

        $this->command->info('Seeded ' . count($titles) . ' always-open live demo exams.');
    }

    private function seedEnglishQuestions(SchoolClass $class, Subject $subject, User $creator): void
    {
        $definitions = [
            ['text' => 'Choose the correct synonym for "Happy":', 'options' => ['Sad', 'Joyful', 'Angry', 'Tired'], 'correct' => 1],
            ['text' => 'Identify the noun in the sentence: "The dog ran fast."', 'options' => ['Ran', 'Fast', 'Dog', 'The'], 'correct' => 2],
            ['text' => 'What is the plural of "Child"?', 'options' => ['Childs', 'Children', 'Childes', 'Child'], 'correct' => 1],
            ['text' => 'Choose the correct article: "___ apple a day keeps the doctor away."', 'options' => ['A', 'An', 'The', 'No article'], 'correct' => 1],
            ['text' => 'Which word is an antonym of "Brave"?', 'options' => ['Courageous', 'Bold', 'Coward', 'Fearless'], 'correct' => 2],
        ];

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
        }
    }
}
