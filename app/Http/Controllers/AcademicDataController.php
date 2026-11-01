<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
use Illuminate\View\View;

class AcademicDataController extends Controller
{
    public function index(): View
    {
        // Mengambil data akademik milik mahasiswa yang sedang login.
        $records = AcademicRecord::where('user_id', auth()->id())
            ->get()
            ->keyBy('course_name');

        // Mengambil daftar mata kuliah target dari konfigurasi SPK.
        $targetCourses = config('spk.target_courses', []);

        return view('academic.index', [
            'records' => $records,
            'targetCourses' => $targetCourses,
        ]);
    }
}
