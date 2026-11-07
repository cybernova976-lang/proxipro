<?php

namespace App\Http\Controllers;

class EmploymentController extends Controller
{
    public function __invoke()
    {
        return response()->view('employment.index')->header('Cache-Control', 'private, no-store');
    }
}
