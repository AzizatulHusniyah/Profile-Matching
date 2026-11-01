<?php

namespace App\Services;

use InvalidArgumentException;

class ProfileMatchingService
{
    /**
     * Calculate Profile Matching using the same direction and weights as the Python module.
     * $studentGrades is keyed by canonical course name and contains a numeric score (1..5).
     */
    public function calculate(array $studentGrades): array
    {
        $config = config('spk');
        $courses = $config['target_courses'];
        $fields = $config['job_fields'];

        $missing = array_values(array_diff($courses, array_keys($studentGrades)));
        $extra = array_values(array_diff(array_keys($studentGrades), $courses));
        if ($missing || $extra || count($studentGrades) !== count($courses)) {
            throw new InvalidArgumentException('Data mahasiswa harus memuat tepat 26 mata kuliah target tanpa duplikat.');
        }

        foreach ($courses as $course) {
            $score = $studentGrades[$course];
            if (!is_numeric($score) || (int) $score != $score || $score < $config['min_score'] || $score > $config['max_score']) {
                throw new InvalidArgumentException("Nilai mata kuliah '{$course}' harus berupa bilangan bulat 1 sampai 5.");
            }
        }

        if (abs($config['core_factor_weight'] + $config['secondary_factor_weight'] - 1.0) > 1e-9) {
            throw new InvalidArgumentException('Total persentase CF dan SF harus 100%.');
        }

        $gapRows = [];
        $weightRows = [];
        $factorResults = [];
        $totalResults = [];

        foreach ($courses as $course) {
            $studentScore = (int) $studentGrades[$course];
            $gapRow = ['Mata Kuliah' => $course];
            $weightRow = ['Mata Kuliah' => $course];
            foreach ($fields as $field) {
                $ideal = $config['ideal_profiles'][$course][$field] ?? null;
                if (!is_int($ideal) || $ideal < 1 || $ideal > 5) {
                    throw new InvalidArgumentException("Nilai ideal untuk {$course} / {$field} tidak valid.");
                }
                // Python uses expert_scores.rsub(student_scores): student score - ideal score.
                $gap = $studentScore - $ideal;
                if (!array_key_exists($gap, $config['gap_weights'])) {
                    throw new InvalidArgumentException("GAP {$gap} di luar rentang bobot yang dikonfigurasi.");
                }
                $gapRow[$field] = $gap;
                $weightRow[$field] = (float) $config['gap_weights'][$gap];
            }
            $gapRows[] = $gapRow;
            $weightRows[] = $weightRow;
        }

        foreach ($fields as $field) {
            $core = [];
            $secondary = [];
            $totalCore = 0.0;
            $totalSecondary = 0.0;
            foreach ($courses as $index => $course) {
                $ideal = (int) $config['ideal_profiles'][$course][$field];
                $weight = (float) $weightRows[$index][$field];
                if (in_array($ideal, $config['core_factor_values'], true)) {
                    $core[] = $weight;
                    $totalCore += $weight;
                } elseif (in_array($ideal, $config['secondary_factor_values'], true)) {
                    $secondary[] = $weight;
                    $totalSecondary += $weight;
                }
            }
            if (count($core) === 0 || count($secondary) === 0) {
                throw new InvalidArgumentException("Bidang '{$field}' harus memiliki Core Factor dan Secondary Factor.");
            }
            $ncf = $totalCore / count($core);
            $nsf = $totalSecondary / count($secondary);
            $factorResults[] = [
                'Bidang Pekerjaan' => $field,
                'Jumlah CF' => count($core),
                'Jumlah SF' => count($secondary),
                'Total Bobot CF' => $totalCore,
                'Total Bobot SF' => $totalSecondary,
                'NCF' => $ncf,
                'NSF' => $nsf,
            ];
            $cfContribution = $ncf * $config['core_factor_weight'];
            $sfContribution = $nsf * $config['secondary_factor_weight'];
            $totalResults[] = [
                'Bidang Pekerjaan' => $field,
                'Kontribusi CF' => $cfContribution,
                'Kontribusi SF' => $sfContribution,
                'Nilai Akhir' => $cfContribution + $sfContribution,
            ];
        }

        usort($totalResults, function (array $a, array $b): int {
            $scoreCompare = $b['Nilai Akhir'] <=> $a['Nilai Akhir'];
            return $scoreCompare !== 0 ? $scoreCompare : strcmp($a['Bidang Pekerjaan'], $b['Bidang Pekerjaan']);
        });

        $lastScore = null;
        $lastRank = 0;
        foreach ($totalResults as $index => &$row) {
            if ($lastScore === null || abs($row['Nilai Akhir'] - $lastScore) > 1e-9) {
                $lastRank = $index + 1;
                $lastScore = $row['Nilai Akhir'];
            }
            $row['Ranking'] = $lastRank;
        }
        unset($row);

        return [
            'gap' => $gapRows,
            'weighted_gap' => $weightRows,
            'factors' => $factorResults,
            'ranking' => $totalResults,
        ];
    }
}
