<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        return view('instructor.subjects.index', [
            'subjects' => $request->user()->subjects()
                ->orderBy('code')
                ->get(['subjects.id', 'subjects.code', 'subjects.name', 'subjects.description']),
        ]);
    }
}
