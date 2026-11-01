@extends('layouts.app')

@section('title', 'Dashboard | SPK Kompetensi Mahasiswa')
@section('nav-dashboard', 'active')

@section('content')
    <div class="page-heading">
        <h1>Dashboard</h1>
        <p>Ringkasan akademik dan visualisasi hasil analisis Profile Matching.</p>
    </div>

    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Dokumen KHS</div>
            <div class="stat-value">{{ $documentsCount }}</div>
            <div class="stat-description">Dokumen tersimpan dari 6 semester</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Mata Kuliah Terbaca</div>
            <div class="stat-value">{{ $recordsCount }}</div>
            <div class="stat-description">Catatan akademik dari KHS</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Bidang Kerja</div>
            <div class="stat-value">{{ count(config('spk.job_fields', [])) }}</div>
            <div class="stat-description">Alternatif rekomendasi</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Hasil Analisis</div>
            <div class="stat-value">{{ $hasRankingData ? 'Tersedia' : '—' }}</div>
            <div class="stat-description">{{ $hasRankingData ? 'Ranking Profile Matching tersimpan' : 'Belum tersedia' }}</div>
        </div>
    </section>

    @if ($hasRankingData || $hasAcademicData)
        <section class="charts-grid" aria-label="Grafik dashboard SPK">
            @if ($hasRankingData)
                <article class="chart-card chart-card-wide">
                    <div class="chart-heading">
                        <div>
                            <h2>Grafik Ranking 10 Bidang Kerja</h2>
                            <p>Nilai akhir Profile Matching. Semakin tinggi nilai, semakin sesuai profil mahasiswa.</p>
                        </div>
                        <span class="chart-chip">10 alternatif</span>
                    </div>
                    <div class="chart-scroll">
                        <svg id="dashboard-ranking-chart" class="ranking-svg" viewBox="0 0 900 {{ max(100, 65 + ($rankingChart->count() * 35)) }}" role="img" aria-labelledby="ranking-chart-title ranking-chart-description">
                            <title id="ranking-chart-title">Ranking bidang kerja berdasarkan nilai akhir</title>
                            <desc id="ranking-chart-description">Grafik batang horizontal untuk nilai akhir sepuluh bidang kerja berdasarkan hasil Profile Matching mahasiswa yang sedang login.</desc>
                            @foreach ([0, 1, 2, 3, 4, 5] as $tick)
                                <line x1="{{ 270 + ($tick * 100) }}" y1="28" x2="{{ 270 + ($tick * 100) }}" y2="{{ 48 + ($rankingChart->count() * 35) }}" stroke="#e7edf5" stroke-width="1" />
                                <text x="{{ 270 + ($tick * 100) }}" y="20" text-anchor="middle" fill="#71809a" font-size="11">{{ $tick }}</text>
                            @endforeach
                            @foreach ($rankingChart as $row)
                                <text x="22" y="{{ $row['y'] + 4 }}" fill="#64748b" font-size="12">{{ $row['rank'] }}.</text>
                                <text x="45" y="{{ $row['y'] + 4 }}" fill="#263b5d" font-size="12">{{ \Illuminate\Support\Str::limit($row['field'], 31) }}</text>
                                <rect x="270" y="{{ $row['y'] - 11 }}" width="500" height="19" rx="5" fill="#edf2f8" />
                                <rect x="270" y="{{ $row['y'] - 11 }}" width="{{ $row['bar_width'] }}" height="19" rx="5" fill="{{ $row['rank'] === 1 ? '#2457d6' : '#6f96f5' }}" />
                                <text x="790" y="{{ $row['y'] + 4 }}" fill="#263b5d" font-size="12" font-weight="700">{{ $row['score_label'] }}</text>
                            @endforeach
                            <text x="520" y="{{ 58 + ($rankingChart->count() * 35) }}" text-anchor="middle" fill="#71809a" font-size="11">Nilai akhir (skala 0–5)</text>
                        </svg>
                    </div>
                </article>
            @endif

            @if ($hasAcademicData)
                <article class="chart-card">
                    <div class="chart-heading">
                        <div>
                            <h2>Profil Kompetensi vs Profil Ideal</h2>
                            <p>Rata-rata nilai mahasiswa dibanding profil ideal untuk bidang {{ $topField ?? 'rekomendasi teratas' }}, per semester.</p>
                        </div>
                    </div>
                    @if ($radarChart['has_data'])
                        <div class="radar-wrap">
                            <svg id="dashboard-radar-chart" class="radar-svg" viewBox="0 0 480 360" role="img" aria-labelledby="radar-chart-title radar-chart-description">
                                <title id="radar-chart-title">Radar profil kompetensi mahasiswa dibanding profil ideal</title>
                                <desc id="radar-chart-description">Membandingkan rata-rata nilai numerik mahasiswa dan rata-rata nilai ideal berbobot SKS untuk {{ $topField }} pada enam semester.</desc>
                                @foreach ($radarChart['grid_polygons'] as $grid)
                                    <polygon points="{{ $grid['points'] }}" fill="none" stroke="#dce5f1" stroke-width="1" />
                                    <text x="{{ $radarChart['center_x'] + 4 }}" y="{{ $radarChart['center_y'] - (105 * $grid['level'] / 5) + 4 }}" fill="#91a0b5" font-size="9">{{ $grid['level'] }}</text>
                                @endforeach
                                @foreach ($radarChart['axes'] as $axis)
                                    <line x1="{{ $radarChart['center_x'] }}" y1="{{ $radarChart['center_y'] }}" x2="{{ $axis['x'] }}" y2="{{ $axis['y'] }}" stroke="#dce5f1" stroke-width="1" />
                                    <text x="{{ $axis['label_x'] }}" y="{{ $axis['label_y'] }}" text-anchor="{{ $axis['anchor'] }}" dominant-baseline="middle" fill="#536783" font-size="11">{{ $axis['label'] }}</text>
                                @endforeach
                                <polygon points="{{ $radarChart['ideal_polygon'] }}" fill="#f59e0b" fill-opacity="0.12" stroke="#d99012" stroke-width="2" />
                                <polygon points="{{ $radarChart['student_polygon'] }}" fill="#3269ed" fill-opacity="0.20" stroke="#3269ed" stroke-width="2.5" />
                            </svg>
                        </div>
                        <div class="chart-legend">
                            <span><i class="legend-dot legend-student"></i>Nilai mahasiswa</span>
                            <span><i class="legend-dot legend-ideal"></i>Profil ideal</span>
                        </div>
                        <p class="chart-footnote">Skala 1–5. Nilai tiap semester dihitung sebagai rata-rata berbobot SKS dari catatan akademik yang tersimpan.</p>
                    @else
                        <div class="chart-empty">Radar akan tampil setelah tersedia data akademik untuk keenam semester dan hasil rekomendasi.</div>
                    @endif
                </article>

                <article class="chart-card">
                    <div class="chart-heading">
                        <div>
                            <h2>Perkembangan Nilai per Semester</h2>
                            <p>Perubahan rata-rata nilai numerik mahasiswa dari semester 1 hingga 6.</p>
                        </div>
                    </div>
                    @if ($semesterLineChart['has_data'])
                        <div class="chart-scroll">
                            <svg id="dashboard-semester-chart" class="line-svg" viewBox="0 0 900 315" role="img" aria-labelledby="semester-chart-title semester-chart-description">
                                <title id="semester-chart-title">Perkembangan nilai numerik per semester</title>
                                <desc id="semester-chart-description">Grafik garis rata-rata nilai numerik berbobot SKS berdasarkan catatan akademik mahasiswa yang sedang login.</desc>
                                @foreach ($semesterLineChart['grid_lines'] as $grid)
                                    <line x1="{{ $semesterLineChart['left'] }}" y1="{{ $grid['y'] }}" x2="{{ $semesterLineChart['right'] }}" y2="{{ $grid['y'] }}" stroke="#e7edf5" stroke-width="1" />
                                    <text x="56" y="{{ $grid['y'] + 4 }}" text-anchor="end" fill="#71809a" font-size="11">{{ $grid['value'] }}</text>
                                @endforeach
                                <polyline points="{{ $semesterLineChart['line_points'] }}" fill="none" stroke="#3269ed" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                @foreach ($semesterLineChart['points'] as $point)
                                    <text x="{{ $point['x'] }}" y="280" text-anchor="middle" fill="#536783" font-size="11">{{ $point['label'] }}</text>
                                    @if ($point['y'] !== null)
                                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5" fill="#fff" stroke="#3269ed" stroke-width="3" />
                                        <text x="{{ $point['x'] }}" y="{{ (float) $point['y'] - 12 }}" text-anchor="middle" fill="#2457d6" font-size="11" font-weight="700">{{ $point['value_label'] }}</text>
                                    @endif
                                @endforeach
                                <text x="460" y="308" text-anchor="middle" fill="#71809a" font-size="11">Rata-rata nilai numerik (skala 1–5)</text>
                            </svg>
                        </div>
                        <p class="chart-footnote">Nilai dihitung dengan pembobotan SKS: jumlah (nilai numerik × SKS) dibagi total SKS pada semester terkait.</p>
                    @else
                        <div class="chart-empty">Grafik akan tampil setelah data akademik KHS tersimpan.</div>
                    @endif
                </article>
            @endif
        </section>
    @else
        <section class="panel empty-dashboard">
            <div class="empty-icon" aria-hidden="true">↗</div>
            <h2>Belum ada data akademik</h2>
            <p>Unggah enam dokumen KHS terlebih dahulu. Setelah data divalidasi dan hasil Profile Matching tersimpan, grafik ranking, radar kompetensi, dan perkembangan nilai akan tampil di sini.</p>
            <a href="{{ route('khs.create') }}" class="button-primary">Unggah KHS</a>
        </section>
    @endif

    <section class="panel process-panel">
        <h2 class="section-title">Tahapan Analisis</h2>
        <div class="steps">
            <div class="step"><div class="step-number">1</div><div class="step-label">Unggah KHS</div></div>
            <div class="step"><div class="step-number">2</div><div class="step-label">Ekstraksi Data</div></div>
            <div class="step"><div class="step-number">3</div><div class="step-label">Profile Matching</div></div>
            <div class="step"><div class="step-number">4</div><div class="step-label">Hasil Rekomendasi</div></div>
        </div>
        <p class="analysis-note">Grafik pada dashboard mengikuti data akademik dan hasil rekomendasi milik akun yang sedang login.</p>
        <a href="{{ route('khs.create') }}" class="button-primary">Kelola Unggahan KHS</a>
    </section>

    <style>
        .stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:22px}
        .stat-card{padding:18px;min-height:112px;background:#fff;border:1px solid #d8e1eb;border-radius:12px;box-shadow:0 3px 12px rgba(27,47,83,.025)}
        .stat-label{margin-bottom:10px;color:#637795;font-size:12px}
        .stat-value{margin-bottom:5px;color:#1b2f53;font-size:25px;font-weight:700}
        .stat-description{color:#71809a;font-size:11px;line-height:1.5}
        .charts-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:24px;align-items:stretch}
        .chart-card{min-width:0;padding:22px;background:#fff;border:1px solid #d8e1eb;border-radius:13px;box-shadow:0 4px 16px rgba(27,47,83,.035)}
        .chart-card-wide{grid-column:1/-1}
        .chart-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:16px}
        .chart-heading h2{margin:0 0 7px;font-size:16px;line-height:1.4;color:#1b2f53}
        .chart-heading p{margin:0;color:#71809a;font-size:12px;line-height:1.6}
        .chart-chip{white-space:nowrap;padding:6px 9px;border-radius:20px;background:#eaf1ff;color:#2457d6;font-size:11px;font-weight:700}
        .chart-scroll{width:100%;overflow-x:auto}
        .ranking-svg{display:block;width:100%;min-width:650px;height:auto}
        .radar-wrap{width:100%;display:flex;justify-content:center}
        .radar-svg{display:block;width:100%;max-width:480px;height:auto;overflow:visible}
        .line-svg{display:block;width:100%;min-width:620px;height:auto}
        .chart-legend{display:flex;justify-content:center;gap:20px;flex-wrap:wrap;margin-top:8px;color:#536783;font-size:12px}
        .chart-legend span{display:inline-flex;align-items:center;gap:7px}
        .legend-dot{display:inline-block;width:10px;height:10px;border-radius:50%}
        .legend-student{background:#3269ed}.legend-ideal{background:#d99012}
        .chart-footnote{margin:14px 0 0;color:#8491a5;font-size:11px;line-height:1.6}
        .chart-empty{display:flex;align-items:center;justify-content:center;min-height:230px;padding:22px;text-align:center;color:#71809a;background:#f8faff;border:1px dashed #d8e1eb;border-radius:10px;font-size:13px;line-height:1.7}
        .empty-dashboard{text-align:center;padding:42px 24px;margin-bottom:24px}
        .empty-icon{display:flex;align-items:center;justify-content:center;width:52px;height:52px;margin:0 auto 14px;border-radius:15px;background:#e8f2ff;color:#3269ed;font-size:28px}
        .empty-dashboard h2{margin:0 0 10px;font-size:19px}
        .empty-dashboard p{max-width:620px;margin:0 auto 22px;color:#71809a;font-size:13px;line-height:1.7}
        .section-title{margin:0 0 28px;font-size:17px}
        .process-panel{margin-top:6px}
        .steps{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:26px}
        .step{position:relative;text-align:center}
        .step:not(:last-child)::after{content:"";position:absolute;top:21px;left:calc(50% + 28px);right:calc(-50% + 28px);height:2px;background:#d8e1eb}
        .step-number{position:relative;z-index:1;display:flex;align-items:center;justify-content:center;width:42px;height:42px;margin:0 auto 12px;border-radius:50%;background:#e8f2ff;color:#3269ed;font-size:14px;font-weight:700}
        .step-label{color:#25334b;font-size:12px}
        .analysis-note{margin-bottom:18px;color:#71809a;font-size:12px;line-height:1.6}
        @media(max-width:1050px){.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.charts-grid{grid-template-columns:1fr}.chart-card-wide{grid-column:auto}}
        @media(max-width:500px){.stats-grid{gap:10px}.stat-card{padding:13px}.chart-card{padding:16px}.steps{gap:5px}.step-label{font-size:10px}.step:not(:last-child)::after{left:calc(50% + 22px);right:calc(-50% + 22px)}}
    </style>
@endsection
