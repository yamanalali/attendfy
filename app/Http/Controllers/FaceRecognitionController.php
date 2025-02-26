<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FaceRecognitionController extends Controller
{
    public function index()
    {
        return view('backend.face-recognition.index'); // Ensure this Blade file exists
    }
}
