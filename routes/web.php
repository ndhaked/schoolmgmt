<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ParentPortal\DashboardController as ParentDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::get('dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');

        Volt::route('academic-years', 'admin.academic-years.index')->name('academic-years');
        Volt::route('classes', 'admin.classes.index')->name('classes');
        Volt::route('subjects', 'admin.subjects.index')->name('subjects');
        Volt::route('students', 'admin.students.index')->name('students');
        Volt::route('teachers', 'admin.teachers.index')->name('teachers');
        Volt::route('parents', 'admin.parents.index')->name('parents');
        Volt::route('question-bank', 'question-bank.index')->name('question-bank');
        Volt::route('exams', 'exams.index')->name('exams');
        Volt::route('results', 'results.index')->name('results');
        Volt::route('students/{student}/marksheet', 'marksheet.show')->name('marksheet');
    });

Route::middleware(['auth', 'verified', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::get('dashboard', TeacherDashboardController::class)->name('dashboard');

        Volt::route('classes', 'teacher.classes.index')->name('classes');
        Volt::route('question-bank', 'question-bank.index')->name('question-bank');
        Volt::route('exams', 'exams.index')->name('exams');
        Volt::route('results', 'results.index')->name('results');
        Volt::route('students/{student}/marksheet', 'marksheet.show')->name('marksheet');
    });

Route::middleware(['auth', 'verified', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('dashboard', StudentDashboardController::class)->name('dashboard');

        Volt::route('exams', 'student.exams.index')->name('exams');
        Volt::route('exams/{exam}/take', 'student.exams.take')->name('exams.take');
        Volt::route('results', 'student.results.index')->name('results');
        Volt::route('marksheet', 'student.results.marksheet')->name('marksheet');
    });

Route::middleware(['auth', 'verified', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::get('dashboard', ParentDashboardController::class)->name('dashboard');

        Volt::route('results', 'parent.results.index')->name('results');
        Volt::route('students/{student}/marksheet', 'marksheet.show')->name('marksheet');
    });

require __DIR__.'/auth.php';
