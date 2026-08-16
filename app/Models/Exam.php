<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'school_class_id', 'subject_id', 'created_by',
        'duration_minutes', 'starts_at', 'ends_at', 'pass_percentage', 'status',
        'results_declared_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'results_declared_at' => 'datetime',
        ];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function totalMarks(): int
    {
        return (int) $this->questions->sum('marks');
    }

    public function isDeclared(): bool
    {
        return $this->results_declared_at !== null;
    }

    public function canDeclare(): bool
    {
        return ! $this->isDeclared()
            && now()->greaterThanOrEqualTo($this->ends_at)
            && $this->attempts()->where('status', 'submitted')->exists();
    }
}
