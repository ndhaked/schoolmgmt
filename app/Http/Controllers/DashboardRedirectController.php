<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        return match (true) {
            $user->hasRole('admin') => redirect()->route('admin.dashboard'),
            $user->hasRole('teacher') => redirect()->route('teacher.dashboard'),
            $user->hasRole('student') => redirect()->route('student.dashboard'),
            $user->hasRole('parent') => redirect()->route('parent.dashboard'),
            default => redirect()->route('profile'),
        };
    }
}
