<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalStudents' => Student::count(),
            'totalTeachers' => Teacher::count(),
            'activeExams' => Exam::where('status', 'published')
                ->where('ends_at', '>=', now())
                ->count(),
            'resultsDeclared' => Exam::whereNotNull('results_declared_at')->count(),
        ]);
    }
}
