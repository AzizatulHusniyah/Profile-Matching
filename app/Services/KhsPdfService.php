<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\Process;

class KhsPdfService
{
    /** Extract a PDF when the expected semester is already known. */
    public function extract(string $path, int $expectedSemester, string $documentName = ''): array
    {
        $recordsByCourse = [];
        $errors = [];

        foreach (['-layout', '-raw'] as $mode) {
            try {
                $text = $this->readPdfText($path, "semester_{$expectedSemester}", $mode);
                foreach ($this->parseText($text, $expectedSemester, $documentName) as $record) {
                    $recordsByCourse[$record['course']] ??= $record;
                }
            } catch (ValidationException $exception) {
                $errors[] = $exception;
            }
        }

        if ($recordsByCourse === []) {
            throw ValidationException::withMessages([
                "semester_{$expectedSemester}" => "PDF {$documentName} tidak berhasil mengekstrak mata kuliah target dari mode layout maupun raw.",
            ]);
        }

        return array_values($recordsByCourse);
    }

    /** Extract a PDF and detect the semester from its content, independent of upload order. */
    public function extractAuto(string $path, string $documentName = ''): array
    {
        $parsedCandidates = [];
        $errors = [];

        // Always inspect both extraction layouts. A mode may find some target
        // rows but silently miss others, so the first non-empty result is not
        // sufficient evidence that the whole transcript has been parsed.
        foreach (['-layout', '-raw'] as $mode) {
            try {
                $text = $this->readPdfText($path, 'documents', $mode);
                $parsedCandidates[] = $this->parseTextAuto($text, $documentName);
            } catch (ValidationException $exception) {
                $errors[] = $exception;
            }
        }

        if ($parsedCandidates === []) {
            throw ValidationException::withMessages([
                'documents' => "PDF {$documentName} belum dapat dibaca dengan benar. Mode layout dan raw gagal mengekstrak mata kuliah target. Pastikan file merupakan KHS berbasis teks dan config/spk.php memuat daftar mata kuliah target.",
            ]);
        }

        $semester = $parsedCandidates[0]['semester'];
        $studentName = $parsedCandidates[0]['student_name'];
        $recordsByCourse = [];

        foreach ($parsedCandidates as $candidate) {
            if ($candidate['semester'] !== $semester) {
                throw ValidationException::withMessages([
                    'documents' => "PDF {$documentName} menghasilkan nomor semester yang berbeda antara mode ekstraksi. Periksa kembali dokumen.",
                ]);
            }

            if ($this->normalizeStudentName($candidate['student_name']) !== $this->normalizeStudentName($studentName)) {
                throw ValidationException::withMessages([
                    'documents' => "PDF {$documentName} menghasilkan nama mahasiswa yang berbeda antara mode ekstraksi. Periksa kembali dokumen.",
                ]);
            }

            foreach ($candidate['records'] as $record) {
                // Prefer the first valid record for each canonical target course.
                $recordsByCourse[$record['course']] ??= $record;
            }
        }

        if ($recordsByCourse === []) {
            throw ValidationException::withMessages([
                'documents' => "Tidak ada mata kuliah target yang berhasil diekstrak dari {$documentName}. Periksa format PDF dan config/spk.php.",
            ]);
        }

        return [
            'semester' => $semester,
            'student_name' => $studentName,
            'records' => array_values($recordsByCourse),
        ];
    }

    /** Detect semester from PDF text, then parse course rows. */
    public function parseTextAuto(string $text, string $documentName = ''): array
    {
        $normalizedText = $this->normalize($text);
        if ($normalizedText === '' || !preg_match('/KARTU\s+HASIL\s+STUDI\s+MAHASISWA/i', $normalizedText)) {
            throw ValidationException::withMessages([
                'documents' => "File {$documentName} bukan Kartu Hasil Studi Mahasiswa yang dikenali.",
            ]);
        }

        if (!preg_match('/\bSemester\s*:\s*(\d+)\b/i', $normalizedText, $semesterMatch)) {
            throw ValidationException::withMessages([
                'documents' => "Nomor semester tidak ditemukan pada file {$documentName}.",
            ]);
        }

        $studentName = $this->extractStudentName($text, $documentName);

        $semester = (int) $semesterMatch[1];
        if ($semester < 1 || $semester > 6) {
            throw ValidationException::withMessages([
                'documents' => "File {$documentName} menunjukkan semester {$semester}; sistem hanya menerima semester 1 sampai 6.",
            ]);
        }

        return [
            'semester' => $semester,
            'student_name' => $studentName,
            'records' => $this->parseText($text, $semester, $documentName),
        ];
    }

    /** Parse both layout-preserved rows and PDFs whose table values are split across lines. */
    public function parseText(string $text, int $expectedSemester, string $documentName = ''): array
    {
        $normalizedText = $this->normalize($text);
        if ($normalizedText === '' || !preg_match('/KARTU\s+HASIL\s+STUDI\s+MAHASISWA/i', $normalizedText)) {
            throw ValidationException::withMessages([
                "semester_{$expectedSemester}" => 'Dokumen bukan Kartu Hasil Studi Mahasiswa yang dikenali.',
            ]);
        }

        if (!preg_match('/\bSemester\s*:\s*(\d+)\b/i', $normalizedText, $semesterMatch)) {
            throw ValidationException::withMessages([
                "semester_{$expectedSemester}" => 'Nomor semester tidak ditemukan pada dokumen KHS.',
            ]);
        }

        $semester = (int) $semesterMatch[1];
        if ($semester !== $expectedSemester) {
            throw ValidationException::withMessages([
                "semester_{$expectedSemester}" => "Dokumen yang dipilih untuk semester {$expectedSemester} ternyata berisi KHS semester {$semester}.",
            ]);
        }

        $periode = '-';
        if (preg_match('/\bPeriode\s*:\s*(\d{4}\s*\/\s*\d{4}\s+(?:Gasal|Genap))\b/i', $normalizedText, $periodMatch)) {
            $periode = preg_replace('/\s+/', ' ', trim($periodMatch[1]));
        }

        $targets = config('spk.target_courses', []);
        if (!is_array($targets) || $targets === []) {
            throw ValidationException::withMessages([
                "semester_{$expectedSemester}" => 'Daftar mata kuliah target kosong. Periksa config/spk.php.',
            ]);
        }

        // Use ASCII-safe normalization because all configured course names use Latin letters.
        $courseLookup = [];
        foreach ($targets as $course) {
            $courseLookup[$this->compactCourseName((string) $course)] = (string) $course;
        }
        $gradeMap = config('spk.grade_map', []);
        $recordsByCourse = [];
        $rowCandidates = 0;

        foreach (preg_split('/\R/u', $text) as $line) {
            $line = $this->normalize($line);
            if ($line === '') {
                continue;
            }

            // Standard pdftotext -layout row: number, course, SKS, grade, Bobot, SKS*N.
            if (!preg_match(
                '/^\s*\d+\s*\.\s*(.*?)\s+(\d{1,2})\s+(A\+|A-|A|B\+|B-|B|C\+|C-|C|D|E)\s+\d+(?:[.,]\d+)?(?:\s+\d+(?:[.,]\d+)?)?\s*$/iu',
                $line,
                $matches
            )) {
                continue;
            }

            $rowCandidates++;
            $courseKey = $this->compactCourseName($matches[1]);
            if (!isset($courseLookup[$courseKey])) {
                continue;
            }

            $course = $courseLookup[$courseKey];
            $grade = strtoupper($matches[3]);
            if (!array_key_exists($grade, $gradeMap)) {
                continue;
            }

            $recordsByCourse[$course] = [
                'document_name' => $documentName,
                'semester' => $semester,
                'periode' => $periode,
                'course' => $course,
                'sks' => (int) $matches[2],
                'grade_letter' => $grade,
                'grade_numeric' => (int) $gradeMap[$grade],
            ];
        }

        // Fallback mirroring Document_Scaning/modules/pdf_table_reader.py:
        // a course name may occupy its own line, followed by SKS and grade lines.
        // Run it even after some table rows matched, because extraction can be partial.
        $lines = array_values(array_filter(array_map(
            fn ($line) => $this->normalize($line),
            preg_split('/\R/u', $text)
        ), fn ($line) => $line !== ''));

        foreach ($lines as $index => $line) {
            $courseLine = preg_replace('/^\d+\s*\.\s*/u', '', $line);
            $courseKey = $this->compactCourseName($courseLine);
            if (!isset($courseLookup[$courseKey])) {
                continue;
            }

            $course = $courseLookup[$courseKey];
            if (isset($recordsByCourse[$course])) {
                continue;
            }

            $hasNumberOnSameLine = preg_match('/^\d+\s*\./u', $line) === 1;
            $previousIsNumber = $index > 0 && preg_match('/^\d+\s*\.$/u', $lines[$index - 1]) === 1;
            if (!$hasNumberOnSameLine && !$previousIsNumber) {
                continue;
            }

            if (!isset($lines[$index + 1], $lines[$index + 2])) {
                continue;
            }

            $sksText = $lines[$index + 1];
            $grade = strtoupper($lines[$index + 2]);
            if (!ctype_digit($sksText) || (int) $sksText < 1 || (int) $sksText > 6 || !array_key_exists($grade, $gradeMap)) {
                continue;
            }

            $recordsByCourse[$course] = [
                'document_name' => $documentName,
                'semester' => $semester,
                'periode' => $periode,
                'course' => $course,
                'sks' => (int) $sksText,
                'grade_letter' => $grade,
                'grade_numeric' => (int) $gradeMap[$grade],
            ];
        }

        if ($recordsByCourse === []) {
            $detail = $rowCandidates > 0
                ? 'Baris tabel terbaca, tetapi nama mata kuliah tidak cocok dengan config/spk.php.'
                : 'Baris tabel tidak sesuai format ekstraksi yang dikenali.';
            throw ValidationException::withMessages([
                "semester_{$expectedSemester}" => "Tidak ada mata kuliah target yang berhasil diekstrak dari {$documentName}. {$detail}",
            ]);
        }

        return array_values($recordsByCourse);
    }

    /**
     * Extract the student name from the KHS header.
     * Supports labels such as "Nama:", "Nama Mahasiswa:", and "Mahasiswa:".
     */
    private function extractStudentName(string $text, string $documentName = ''): string
    {
        $lines = preg_split('/\R/u', $text);

        foreach ($lines as $index => $line) {
            $line = trim(str_replace(["\u{00A0}", "\u{202F}", "\u{2007}"], ' ', $line));

            if (preg_match('/^\s*(?:Nama(?:\s+Mahasiswa)?|Mahasiswa)\s*[:：]\s*(.*?)(?=\s+Semester\s*[:：]|$)/iu', $line, $matches)) {
                // Stop before the Semester field, which appears on the same
                // physical line in both pdftotext -layout and -raw output.
                $name = trim($matches[1]);

                // Some PDF layouts place the name on the line after its label.
                if ($name === '' && isset($lines[$index + 1])) {
                    $name = trim($lines[$index + 1]);
                }

                // Defensive cleanup for PDF extraction variants that append
                // the semester field to the name line.
                $name = (string) preg_replace('/\s+Semester\s*[:：].*$/iu', '', $name);
                $name = trim($name);

                // Avoid accidentally interpreting the next metadata label as a name.
                if ($name !== '' && !preg_match('/^(?:NIM|NPM|Nomor Induk|Semester|Program Studi|Prodi)\s*[:：]/iu', $name)) {
                    $name = (string) preg_replace('/\s+/u', ' ', $name);
                    if (mb_strlen($name) >= 2) {
                        return $name;
                    }
                }
            }
        }

        throw ValidationException::withMessages([
            'documents' => "Nama mahasiswa tidak ditemukan pada PDF {$documentName}. Pastikan bagian identitas/nama mahasiswa terbaca pada dokumen KHS.",
        ]);
    }

    private function normalizeStudentName(string $name): string
    {
        $name = function_exists('mb_strtolower')
            ? mb_strtolower(trim($name), 'UTF-8')
            : strtolower(trim($name));

        $name = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);

        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    private function readPdfText(string $path, string $errorKey, string $mode): string
    {
        if (!is_file($path)) {
            throw new RuntimeException('File PDF KHS tidak ditemukan.');
        }

        try {
            $process = new Process(['pdftotext', $mode, $path, '-']);
            $process->setTimeout(30);
            $process->run();
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                $errorKey => 'PDF tidak dapat diproses. Pastikan Poppler (pdftotext) sudah terpasang pada server.',
            ]);
        }

        if (!$process->isSuccessful()) {
            throw ValidationException::withMessages([
                $errorKey => 'PDF tidak dapat dibaca. Pastikan file merupakan PDF yang valid dan dapat diekstrak.',
            ]);
        }

        return $process->getOutput();
    }

    private function normalize(string $value): string
    {
        $value = str_replace(["\u{00A0}", "\u{202F}", "\u{2007}"], ' ', $value);
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function compactCourseName(string $value): string
    {
        $value = strtolower($this->normalize($value));
        return (string) preg_replace('/[^a-z0-9]+/i', '', $value);
    }
}
