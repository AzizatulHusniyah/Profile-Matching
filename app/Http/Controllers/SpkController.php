<?php

namespace App\Http\Controllers;

use App\Services\ProfileMatchingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SpkController extends Controller
{
    /** Recalculate from persisted academic records if an explicit refresh is needed. */
    public function recalculate(Request $request, ProfileMatchingService $service)
    {
        $records = \App\Models\AcademicRecord::where('user_id', auth()->id())->get();
        if ($records->count() !== count(config('spk.target_courses'))) {
            throw ValidationException::withMessages(['documents' => 'Perhitungan membutuhkan tepat 26 mata kuliah target.']);
        }
        $grades = $records->pluck('grade_numeric', 'course_name')->all();
        $calculation = $service->calculate($grades);
        \App\Models\SpkResult::updateOrCreate(['user_id' => auth()->id()], [
            'gap_results' => $calculation['gap'],
            'weighted_gap_results' => $calculation['weighted_gap'],
            'factor_results' => $calculation['factors'],
            'ranking_results' => $calculation['ranking'],
        ]);
        return redirect()->route('recommendations.index')->with('success', 'Hasil SPK berhasil dihitung ulang.');
    }
}
