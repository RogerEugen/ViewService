<?php

namespace App\Http\Controllers\Register;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegistrarController extends Controller
{
    //
    public function ManageUser(){
        return inertia ('Register/ManageUser');
    }
}
