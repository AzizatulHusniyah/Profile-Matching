<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\KhsDocument;
use App\Models\SpkResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KhsReplacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_delete_their_existing_khs_package_and_related_results(): void
    {
        Storage::fake('local');

        $student = User::factory()->create();
        $otherStudent = User::factory()->create();

        $studentPath = 'khs/'.$student->id.'/semester-1.pdf';
        $otherPath = 'khs/'.$otherStudent->id.'/semester-1.pdf';
        Storage::disk('local')->put($studentPath, 'student pdf');
        Storage::disk('local')->put($otherPath, 'other student pdf');

        $studentDocument = KhsDocument::create([
            'user_id' => $student->id,
            'semester' => 1,
            'original_filename' => 'semester-1.pdf',
            'file_path' => $studentPath,
            'file_hash' => hash('sha256', 'student pdf'),
            'status' => 'processed',
        ]);
        $otherDocument = KhsDocument::create([
            'user_id' => $otherStudent->id,
            'semester' => 1,
            'original_filename' => 'semester-1.pdf',
            'file_path' => $otherPath,
            'file_hash' => hash('sha256', 'other student pdf'),
            'status' => 'processed',
        ]);

        AcademicRecord::create([
            'user_id' => $student->id,
            'khs_document_id' => $studentDocument->id,
            'semester' => 1,
            'periode' => '2024/2025 Gasal',
            'course_name' => 'Algoritma dan Pemrograman',
            'sks' => 3,
            'grade_letter' => 'A',
            'grade_numeric' => 5,
        ]);
        AcademicRecord::create([
            'user_id' => $otherStudent->id,
            'khs_document_id' => $otherDocument->id,
            'semester' => 1,
            'periode' => '2024/2025 Gasal',
            'course_name' => 'Algoritma dan Pemrograman',
            'sks' => 3,
            'grade_letter' => 'A',
            'grade_numeric' => 5,
        ]);

        SpkResult::create([
            'user_id' => $student->id,
            'gap_results' => ['example' => 1],
            'weighted_gap_results' => ['example' => 1],
            'factor_results' => ['example' => 1],
            'ranking_results' => ['example' => 1],
        ]);
        SpkResult::create([
            'user_id' => $otherStudent->id,
            'gap_results' => ['example' => 1],
            'weighted_gap_results' => ['example' => 1],
            'factor_results' => ['example' => 1],
            'ranking_results' => ['example' => 1],
        ]);

        $response = $this->actingAs($student)->delete(route('khs.destroy'));

        $response->assertRedirect(route('khs.create'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('khs_documents', ['id' => $studentDocument->id]);
        $this->assertDatabaseMissing('academic_records', ['user_id' => $student->id]);
        $this->assertDatabaseMissing('spk_results', ['user_id' => $student->id]);
        Storage::disk('local')->assertMissing($studentPath);

        $this->assertDatabaseHas('khs_documents', ['id' => $otherDocument->id]);
        $this->assertDatabaseHas('academic_records', ['user_id' => $otherStudent->id]);
        $this->assertDatabaseHas('spk_results', ['user_id' => $otherStudent->id]);
        Storage::disk('local')->assertExists($otherPath);
    }

    public function test_guest_cannot_delete_khs_documents(): void
    {
        $response = $this->delete(route('khs.destroy'));

        $response->assertRedirect(route('login'));
    }

    public function test_upload_page_offers_delete_before_reupload_when_documents_exist(): void
    {
        $student = User::factory()->create();
        KhsDocument::create([
            'user_id' => $student->id,
            'semester' => 1,
            'original_filename' => 'semester-1.pdf',
            'file_path' => 'khs/'.$student->id.'/semester-1.pdf',
            'file_hash' => hash('sha256', 'student pdf'),
            'status' => 'processed',
        ]);

        $response = $this->actingAs($student)->get(route('khs.create'));

        $response->assertOk();
        $response->assertSee('Hapus Unggahan KHS');
        $response->assertSee('action="'.route('khs.destroy').'"', false);
        $response->assertDontSee('id="khs-upload-form"', false);
    }
}
