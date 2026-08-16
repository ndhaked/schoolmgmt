<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id', 'student_id', 'started_at', 'submitted_at', 'status', 'obtained_marks',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    public function deadline(): \Illuminate\Support\Carbon
    {
        $byDuration = $this->started_at->copy()->addMinutes($this->exam->duration_minutes);

        return $byDuration->lessThan($this->exam->ends_at) ? $byDuration : $this->exam->ends_at;
    }

    public function isExpired(): bool
    {
        return now()->greaterThanOrEqualTo($this->deadline());
    }
}
