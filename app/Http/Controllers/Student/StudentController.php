<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    //

    public function MyInfo(){
        return Inertia::render('Student/MyInfo');
    }
}
