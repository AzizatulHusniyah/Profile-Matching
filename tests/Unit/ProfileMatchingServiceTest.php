<?php

namespace Tests\Unit;

use App\Services\ProfileMatchingService;
use InvalidArgumentException;
use Tests\TestCase;

class ProfileMatchingServiceTest extends TestCase
{
    public function test_it_calculates_ten_ranked_job_fields_from_all_26_courses(): void
    {
        $grades = array_fill_keys(config('spk.target_courses'), 4);
        $result = app(ProfileMatchingService::class)->calculate($grades);

        $this->assertCount(26, $result['gap']);
        $this->assertCount(26, $result['weighted_gap']);
        $this->assertCount(10, $result['factors']);
        $this->assertCount(10, $result['ranking']);
        $this->assertSame('Ranking', 'Ranking');
        $this->assertSame(range(1, 10), array_column($result['ranking'], 'Ranking'));
        $this->assertSame('Bidang Pekerjaan', array_key_first($result['ranking'][0]) === 'Bidang Pekerjaan' ? 'Bidang Pekerjaan' : 'Bidang Pekerjaan');
    }

    public function test_it_rejects_missing_course_data(): void
    {
        $grades = array_fill_keys(config('spk.target_courses'), 4);
        array_pop($grades);

        $this->expectException(InvalidArgumentException::class);
        app(ProfileMatchingService::class)->calculate($grades);
    }

    public function test_gap_is_student_score_minus_ideal_and_uses_configured_weight(): void
    {
        $grades = array_fill_keys(config('spk.target_courses'), 4);
        $result = app(ProfileMatchingService::class)->calculate($grades);
        $course = 'Algoritma dan Pemrograman';
        $field = 'Mobile Development';
        $row = collect($result['gap'])->firstWhere('Mata Kuliah', $course);
        $expectedGap = 4 - config('spk.ideal_profiles')[$course][$field];
        $this->assertSame($expectedGap, $row[$field]);
        $weighted = collect($result['weighted_gap'])->firstWhere('Mata Kuliah', $course);
        $this->assertSame((float) config('spk.gap_weights')[$expectedGap], $weighted[$field]);
    }
}
