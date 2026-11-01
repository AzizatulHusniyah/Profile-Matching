<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
use App\Models\KhsDocument;
use App\Models\SpkResult;
use App\Services\KhsPdfService;
use App\Services\ProfileMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class KhsController extends Controller
{
    public function create()
    {
        $documents = KhsDocument::where('user_id', auth()->id())
            ->orderBy('semester')
            ->get()
            ->keyBy('semester');

        return view('khs.upload', compact('documents'));
    }

    /**
     * Remove only the authenticated student's current KHS package, academic
     * records, and Profile Matching result so a fresh six-file upload is possible.
     */
    public function destroy()
    {
        $userId = (int) auth()->id();

        $documents = KhsDocument::where('user_id', $userId)->get(['id', 'file_path']);
        if ($documents->isEmpty()) {
            return redirect()->route('khs.create')
                ->with('error', 'Belum ada unggahan KHS yang perlu dihapus.');
        }

        $paths = $documents->pluck('file_path')->filter()->values()->all();

        DB::transaction(function () use ($userId): void {
            // Delete dependent rows explicitly, then the uploaded documents and result.
            AcademicRecord::where('user_id', $userId)->delete();
            KhsDocument::where('user_id', $userId)->delete();
            SpkResult::where('user_id', $userId)->delete();
        });

        // Files are private on the local disk and paths were fetched only for this user.
        if ($paths !== []) {
            Storage::disk('local')->delete($paths);
        }

        return redirect()->route('khs.create')->with(
            'success',
            'Unggahan KHS, data akademik, dan hasil rekomendasi lama berhasil dihapus. Kamu sekarang dapat mengunggah enam KHS baru.'
        );
    }

    public function store(Request $request, KhsPdfService $pdfService, ProfileMatchingService $matchingService)
    {
        $validated = $request->validate([
            'documents' => ['required', 'array', 'size:6'],
            'documents.*' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'documents.required' => 'Pilih enam file PDF KHS sekaligus.',
            'documents.array' => 'Dokumen harus dikirim dalam bentuk kumpulan file.',
            'documents.size' => 'Pilih tepat enam file PDF KHS, satu untuk setiap semester 1 sampai 6.',
            'documents.*.required' => 'Salah satu file KHS tidak terbaca. Pilih ulang enam PDF.',
            'documents.*.file' => 'Salah satu unggahan bukan file yang valid.',
            'documents.*.mimes' => 'Semua dokumen harus berformat PDF.',
            'documents.*.max' => 'Ukuran setiap PDF maksimal 10 MB.',
        ]);

        $userId = (int) auth()->id();
        if (KhsDocument::where('user_id', $userId)->exists()) {
            throw ValidationException::withMessages([
                'documents' => 'Dokumen KHS sudah pernah diunggah. Hubungi administrator untuk memperbarui data.',
            ]);
        }

        // Parse all files before saving anything. Semester comes from the PDF content, not filename/order.
        $filesBySemester = [];
        $parsedBySemester = [];
        $seenHashes = [];
        $seenCourses = [];
        $consistentStudentName = null;
        $consistentStudentNameKey = null;

        foreach ($validated['documents'] as $file) {
            $filename = $file->getClientOriginalName();
            $hash = hash_file('sha256', $file->getRealPath());
            if (isset($seenHashes[$hash])) {
                throw ValidationException::withMessages([
                    'documents' => "File {$filename} merupakan duplikat dari PDF lain yang dipilih.",
                ]);
            }
            $seenHashes[$hash] = true;

            $documentData = $pdfService->extractAuto($file->getRealPath(), $filename);
            $semester = $documentData['semester'];
            $pdfStudentName = $documentData['student_name'];

            $normalizedStudentName = function_exists('mb_strtolower')
                ? mb_strtolower(trim($pdfStudentName), 'UTF-8')
                : strtolower(trim($pdfStudentName));
            $normalizedStudentName = trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalizedStudentName));
            $normalizedStudentName = (string) preg_replace('/\s+/u', ' ', $normalizedStudentName);

            if ($consistentStudentNameKey === null) {
                $consistentStudentName = $pdfStudentName;
                $consistentStudentNameKey = $normalizedStudentName;
            } elseif ($consistentStudentNameKey !== $normalizedStudentName) {
                throw ValidationException::withMessages([
                    'documents' => "Nama mahasiswa pada PDF semester {$semester} ({$pdfStudentName}) tidak konsisten dengan PDF lainnya ({$consistentStudentName}). Pastikan keenam PDF KHS berasal dari mahasiswa yang sama.",
                ]);
            }

            if (isset($filesBySemester[$semester])) {
                throw ValidationException::withMessages([
                    'documents' => "Ada lebih dari satu PDF untuk semester {$semester}. Pilih tepat satu KHS untuk setiap semester.",
                ]);
            }

            $filesBySemester[$semester] = $file;
            $parsedBySemester[$semester] = $documentData['records'];

            foreach ($documentData['records'] as $record) {
                if (isset($seenCourses[$record['course']])) {
                    throw ValidationException::withMessages([
                        'documents' => "Mata kuliah '{$record['course']}' ditemukan lebih dari sekali. Periksa apakah ada PDF semester yang duplikat atau data KHS yang berulang.",
                    ]);
                }
                $seenCourses[$record['course']] = true;
            }
        }

        $missingSemesters = array_values(array_diff(range(1, 6), array_keys($filesBySemester)));
        if ($missingSemesters !== []) {
            throw ValidationException::withMessages([
                'documents' => 'Semester yang belum ditemukan: '.implode(', ', $missingSemesters).'. Pastikan PDF yang dipilih mencakup semester 1 sampai 6.',
            ]);
        }

        $targetCourses = config('spk.target_courses', []);
        $missingCourses = array_values(array_diff($targetCourses, array_keys($seenCourses)));
        if ($missingCourses !== []) {
            throw ValidationException::withMessages([
                'documents' => 'Kumpulan KHS belum mencakup semua 26 mata kuliah target. Belum ditemukan: '.implode(', ', $missingCourses).'.',
            ]);
        }

        $studentGrades = [];
        foreach ($parsedBySemester as $records) {
            foreach ($records as $record) {
                $studentGrades[$record['course']] = $record['grade_numeric'];
            }
        }
        $calculation = $matchingService->calculate($studentGrades);
        $storedPaths = [];

        try {
            DB::transaction(function () use ($filesBySemester, $parsedBySemester, $userId, $calculation, $consistentStudentName, &$storedPaths) {
                ksort($filesBySemester);
                foreach ($filesBySemester as $semester => $file) {
                    $hash = hash_file('sha256', $file->getRealPath());
                    $path = $file->store("khs/{$userId}", 'local');
                    if (!$path) {
                        throw new \RuntimeException('File KHS gagal disimpan.');
                    }
                    $storedPaths[] = $path;

                    $document = KhsDocument::create([
                        'user_id' => $userId,
                        'semester' => $semester,
                        'student_name' => $consistentStudentName,
                        'original_filename' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_hash' => $hash,
                        'status' => 'processed',
                    ]);

                    foreach ($parsedBySemester[$semester] as $record) {
                        AcademicRecord::create([
                            'user_id' => $userId,
                            'khs_document_id' => $document->id,
                            'semester' => $record['semester'],
                            'periode' => $record['periode'],
                            'course_name' => $record['course'],
                            'sks' => $record['sks'],
                            'grade_letter' => $record['grade_letter'],
                            'grade_numeric' => $record['grade_numeric'],
                        ]);
                    }
                }

                SpkResult::updateOrCreate(['user_id' => $userId], [
                    'gap_results' => $calculation['gap'],
                    'weighted_gap_results' => $calculation['weighted_gap'],
                    'factor_results' => $calculation['factors'],
                    'ranking_results' => $calculation['ranking'],
                ]);
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return redirect()->route('khs.create')->with(
            'success',
            "Enam dokumen KHS berhasil divalidasi. Nama mahasiswa '{$consistentStudentName}' terdeteksi konsisten pada seluruh PDF. Data akademik dan hasil Profile Matching telah disimpan."
        );
    }
}
