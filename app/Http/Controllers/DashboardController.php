<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
use App\Models\KhsDocument;
use App\Models\SpkResult;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = (int) auth()->id();
        $documentsCount = KhsDocument::where('user_id', $userId)->count();
        $records = AcademicRecord::where('user_id', $userId)
            ->orderBy('semester')
            ->orderBy('course_name')
            ->get();
        $result = SpkResult::where('user_id', $userId)->first();

        $rankingChart = collect($result?->ranking_results ?? [])
            ->filter(fn ($row) => is_array($row)
                && isset($row['Bidang Pekerjaan'])
                && is_numeric($row['Nilai Akhir'] ?? null))
            ->sortBy(fn ($row) => (int) ($row['Ranking'] ?? 999))
            ->take(10)
            ->values()
            ->map(fn ($row, $index) => [
                'rank' => (int) ($row['Ranking'] ?? ($index + 1)),
                'field' => (string) $row['Bidang Pekerjaan'],
                'score' => (float) $row['Nilai Akhir'],
                'score_label' => number_format((float) $row['Nilai Akhir'], 2, ',', '.'),
                'y' => 42 + ($index * 35),
                'bar_width' => max(0, min(5, (float) $row['Nilai Akhir'])) / 5 * 500,
            ]);

        $topField = $rankingChart->first()['field'] ?? null;
        $idealProfiles = config('spk.ideal_profiles', []);
        $recordsBySemester = $records->groupBy('semester');
        $semesterSeries = collect(range(1, 6))->map(function (int $semester) use ($recordsBySemester, $idealProfiles, $topField) {
            /** @var Collection<int, AcademicRecord> $semesterRecords */
            $semesterRecords = $recordsBySemester->get($semester, collect());
            $totalSks = 0.0;
            $weightedStudentScore = 0.0;
            $weightedIdealScore = 0.0;
            $idealSks = 0.0;

            foreach ($semesterRecords as $record) {
                $sks = (float) $record->sks;
                if ($sks <= 0) {
                    continue;
                }

                $totalSks += $sks;
                $weightedStudentScore += ((float) $record->grade_numeric) * $sks;

                $ideal = $topField !== null
                    ? ($idealProfiles[$record->course_name][$topField] ?? null)
                    : null;
                if (is_numeric($ideal)) {
                    $weightedIdealScore += ((float) $ideal) * $sks;
                    $idealSks += $sks;
                }
            }

            $studentAverage = $totalSks > 0 ? $weightedStudentScore / $totalSks : null;
            $idealAverage = $idealSks > 0 ? $weightedIdealScore / $idealSks : null;

            return [
                'semester' => $semester,
                'label' => 'Semester '.$semester,
                'student' => $studentAverage === null ? null : round($studentAverage, 3),
                'ideal' => $idealAverage === null ? null : round($idealAverage, 3),
                'student_label' => $studentAverage === null ? 'Belum ada data' : number_format($studentAverage, 2, ',', '.'),
                'ideal_label' => $idealAverage === null ? 'Belum ada data' : number_format($idealAverage, 2, ',', '.'),
            ];
        });

        $radarChart = $this->buildRadarGeometry($semesterSeries->all());
        $semesterLineChart = $this->buildLineGeometry($semesterSeries->all());
        $hasAcademicData = $records->isNotEmpty();
        $hasRankingData = $rankingChart->isNotEmpty();

        return view('dashboard', [
            'documentsCount' => $documentsCount,
            'recordsCount' => $records->count(),
            'result' => $result,
            'rankingChart' => $rankingChart,
            'topField' => $topField,
            'semesterSeries' => $semesterSeries,
            'radarChart' => $radarChart,
            'semesterLineChart' => $semesterLineChart,
            'hasAcademicData' => $hasAcademicData,
            'hasRankingData' => $hasRankingData,
        ]);
    }

    /** @param array<int, array{semester:int,label:string,student:?float,ideal:?float,student_label:string,ideal_label:string}> $series */
    private function buildRadarGeometry(array $series): array
    {
        $centerX = 240.0;
        $centerY = 175.0;
        $radius = 105.0;
        $axisCount = count($series);
        $gridPolygons = [];

        foreach (range(1, 5) as $level) {
            $points = [];
            foreach (range(0, $axisCount - 1) as $index) {
                $angle = deg2rad(-90 + (360 / $axisCount) * $index);
                $distance = $radius * $level / 5;
                $points[] = $this->coordinate($centerX + cos($angle) * $distance, $centerY + sin($angle) * $distance);
            }
            $gridPolygons[] = ['level' => $level, 'points' => implode(' ', $points)];
        }

        $axes = [];
        $studentPoints = [];
        $idealPoints = [];
        $studentValues = [];
        $idealValues = [];

        foreach ($series as $index => $item) {
            $angle = deg2rad(-90 + (360 / $axisCount) * $index);
            $outerX = $centerX + cos($angle) * ($radius + 2);
            $outerY = $centerY + sin($angle) * ($radius + 2);
            $labelX = $centerX + cos($angle) * ($radius + 27);
            $labelY = $centerY + sin($angle) * ($radius + 27);
            $anchor = cos($angle) > 0.25 ? 'start' : (cos($angle) < -0.25 ? 'end' : 'middle');

            $axes[] = [
                'x' => $this->formatNumber($outerX),
                'y' => $this->formatNumber($outerY),
                'label_x' => $this->formatNumber($labelX),
                'label_y' => $this->formatNumber($labelY),
                'anchor' => $anchor,
                'label' => $item['label'],
            ];

            $studentValues[] = $item['student'];
            $idealValues[] = $item['ideal'];
            $studentScore = $item['student'] ?? 0;
            $idealScore = $item['ideal'] ?? 0;
            $studentDistance = $radius * max(0, min(5, $studentScore)) / 5;
            $idealDistance = $radius * max(0, min(5, $idealScore)) / 5;
            $studentPoints[] = $this->coordinate(
                $centerX + cos($angle) * $studentDistance,
                $centerY + sin($angle) * $studentDistance,
            );
            $idealPoints[] = $this->coordinate(
                $centerX + cos($angle) * $idealDistance,
                $centerY + sin($angle) * $idealDistance,
            );
        }

        $hasRadarData = count(array_filter($studentValues, fn ($value) => $value !== null)) === count($series)
            && count(array_filter($idealValues, fn ($value) => $value !== null)) === count($series)
            && count($series) === 6;

        return [
            'center_x' => $centerX,
            'center_y' => $centerY,
            'grid_polygons' => $gridPolygons,
            'axes' => $axes,
            'student_polygon' => implode(' ', $studentPoints),
            'ideal_polygon' => implode(' ', $idealPoints),
            'student_values' => $studentValues,
            'ideal_values' => $idealValues,
            'has_data' => $hasRadarData,
        ];
    }

    /** @param array<int, array{semester:int,label:string,student:?float,ideal:?float,student_label:string,ideal_label:string}> $series */
    private function buildLineGeometry(array $series): array
    {
        $left = 72.0;
        $right = 850.0;
        $top = 28.0;
        $bottom = 250.0;
        $points = [];
        $gridLines = [];
        $count = max(1, count($series));

        foreach (range(1, 5) as $value) {
            $y = $bottom - (($value - 1) / 4) * ($bottom - $top);
            $gridLines[] = ['value' => $value, 'y' => $this->formatNumber($y)];
        }

        foreach ($series as $index => $item) {
            $x = $count === 1 ? ($left + $right) / 2 : $left + ($index * ($right - $left) / ($count - 1));
            $y = $item['student'] === null
                ? null
                : $bottom - ((max(1, min(5, $item['student'])) - 1) / 4) * ($bottom - $top);
            $points[] = [
                'x' => $this->formatNumber($x),
                'y' => $y === null ? null : $this->formatNumber($y),
                'label' => $item['label'],
                'value_label' => $item['student_label'],
                'value' => $item['student'],
            ];
        }

        $linePoints = collect($points)
            ->filter(fn ($point) => $point['y'] !== null)
            ->map(fn ($point) => $point['x'].','.$point['y'])
            ->implode(' ');

        return [
            'left' => $left,
            'right' => $right,
            'top' => $top,
            'bottom' => $bottom,
            'grid_lines' => $gridLines,
            'points' => $points,
            'line_points' => $linePoints,
            'has_data' => collect($points)->contains(fn ($point) => $point['value'] !== null),
        ];
    }

    private function coordinate(float $x, float $y): string
    {
        return $this->formatNumber($x).','.$this->formatNumber($y);
    }

    private function formatNumber(float $number): string
    {
        return number_format($number, 2, '.', '');
    }
}
