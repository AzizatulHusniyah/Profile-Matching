<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\KhsDocument;
use App\Models\SpkResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_rankings_radar_and_semester_trend_from_authenticated_users_data(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();

        $ranking = [];
        foreach (config('spk.job_fields') as $index => $field) {
            $ranking[] = [
                'Bidang Pekerjaan' => $field,
                'Nilai Akhir' => 4.8 - ($index * 0.2),
                'Ranking' => $index + 1,
            ];
        }

        SpkResult::create([
            'user_id' => $student->id,
            'gap_results' => [],
            'weighted_gap_results' => [],
            'factor_results' => [],
            'ranking_results' => $ranking,
        ]);
        SpkResult::create([
            'user_id' => $otherStudent->id,
            'gap_results' => [],
            'weighted_gap_results' => [],
            'factor_results' => [],
            'ranking_results' => [[
                'Bidang Pekerjaan' => 'PRIVATE OTHER STUDENT FIELD',
                'Nilai Akhir' => 5,
                'Ranking' => 1,
            ]],
        ]);

        foreach (range(1, 6) as $semester) {
            $document = KhsDocument::create([
                'user_id' => $student->id,
                'semester' => $semester,
                'original_filename' => "semester-{$semester}.pdf",
                'file_path' => "khs/{$student->id}/semester-{$semester}.pdf",
                'file_hash' => hash('sha256', "student-{$semester}"),
                'status' => 'processed',
            ]);

            $course = config('spk.target_courses')[$semester - 1];
            AcademicRecord::create([
                'user_id' => $student->id,
                'khs_document_id' => $document->id,
                'semester' => $semester,
                'periode' => '2024/2025 Gasal',
                'course_name' => $course,
                'sks' => 3,
                'grade_letter' => 'A',
                'grade_numeric' => 3 + (($semester - 1) % 3),
            ]);
        }

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Grafik Ranking 10 Bidang Kerja');
        $response->assertSee('Profil Kompetensi vs Profil Ideal');
        $response->assertSee('Perkembangan Nilai per Semester');
        $response->assertSee('Software Development');
        $response->assertSee('Semester 6');
        $response->assertDontSee('PRIVATE OTHER STUDENT FIELD');
        $response->assertSee('dashboard-ranking-chart', false);
        $response->assertSee('dashboard-radar-chart', false);
        $response->assertSee('dashboard-semester-chart', false);
    }

    public function test_dashboard_shows_an_empty_state_when_student_has_not_uploaded_khs(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Belum ada data akademik');
        $response->assertDontSee('dashboard-ranking-chart', false);
    }
}
