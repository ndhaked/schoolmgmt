<?php

namespace App\Support;

use App\Models\ExamAttempt;
use App\Models\Student;

class MarksheetBuilder
{
    public static function build(Student $student): array
    {
        $attempts = ExamAttempt::with(['exam.subject'])
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->whereHas('exam', fn ($q) => $q->whereNotNull('results_declared_at'))
            ->get()
            ->sortByDesc(fn ($attempt) => $attempt->exam->results_declared_at);

        $rows = $attempts->map(function (ExamAttempt $attempt) {
            $total = $attempt->exam->totalMarks();
            $percentage = $total > 0 ? round(($attempt->obtained_marks / $total) * 100, 1) : 0;

            return [
                'subject' => $attempt->exam->subject->name,
                'exam_title' => $attempt->exam->title,
                'obtained' => (float) $attempt->obtained_marks,
                'total' => $total,
                'percentage' => $percentage,
                'grade' => self::gradeFor($percentage),
                'passed' => $percentage >= $attempt->exam->pass_percentage,
            ];
        })->values()->all();

        $totalObtained = array_sum(array_column($rows, 'obtained'));
        $totalPossible = array_sum(array_column($rows, 'total'));

        $summary = [
            'obtained' => $totalObtained,
            'total' => $totalPossible,
            'percentage' => $totalPossible > 0 ? round(($totalObtained / $totalPossible) * 100, 1) : 0,
            'passed' => count($rows) > 0 && collect($rows)->every(fn ($row) => $row['passed']),
        ];

        return ['rows' => $rows, 'summary' => $summary];
    }

    private static function gradeFor(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'A+',
            $percentage >= 75 => 'A',
            $percentage >= 60 => 'B',
            $percentage >= 40 => 'C',
            default => 'F',
        };
    }
}
