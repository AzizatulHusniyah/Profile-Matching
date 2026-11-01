<?php

namespace Tests\Unit;

use App\Services\KhsPdfService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KhsPdfServiceTest extends TestCase
{
    public function test_it_extracts_a_target_course_and_converts_letter_grade(): void
    {
        $text = "KARTU HASIL STUDI MAHASISWA\nPeriode : 2024/2025 Gasal\nSemester :1\n 1.    Algoritma dan Pemrograman   3   A   4   12\n";
        $records = app(KhsPdfService::class)->parseText($text, 1, 'khs-semester-1.pdf');
        $this->assertCount(1, $records);
        $this->assertSame('Algoritma dan Pemrograman', $records[0]['course']);
        $this->assertSame(5, $records[0]['grade_numeric']);
        $this->assertSame(3, $records[0]['sks']);
    }

    public function test_it_fills_split_table_rows_even_when_other_rows_already_matched(): void
    {
        $text = implode("\n", [
            'KARTU HASIL STUDI MAHASISWA',
            'Periode : 2024/2025 Gasal',
            'Semester :3',
            '1. Analisis dan Disain Perangkat Lunak 2 B+ 3.5 7',
            '2.',
            'Basis Data Lanjut',
            '2',
            'A',
            '4',
            '8',
        ]);

        $records = app(KhsPdfService::class)->parseText($text, 3, 'khs-semester-3.pdf');

        $this->assertSame(
            ['Analisis dan Disain Perangkat Lunak', 'Basis Data Lanjut'],
            array_column($records, 'course')
        );
        $this->assertSame(2, $records[1]['sks']);
        $this->assertSame('A', $records[1]['grade_letter']);
    }

    public function test_it_rejects_wrong_semester(): void
    {
        $text = "KARTU HASIL STUDI MAHASISWA\nSemester :2\n";
        $this->expectException(ValidationException::class);
        app(KhsPdfService::class)->parseText($text, 1, 'khs.pdf');
    }
}
