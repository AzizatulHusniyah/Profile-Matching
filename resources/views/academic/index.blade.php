
@extends('layouts.app')

@section('title', 'Data Akademik | SPK Kompetensi Mahasiswa')
@section('nav-academic', 'active')

@section('content')

<div class="page-heading">
    <h1>Data Akademik</h1>
    <p>Data mata kuliah dan nilai yang diperoleh dari dokumen KHS.</p>
</div>

<section class="panel academic-panel">

    <div class="academic-panel-heading">
        <h2>Daftar Data Akademik</h2>
        <span>Semester 1–6</span>
    </div>

    <div class="academic-table-wrap">
        <table class="academic-table">

            <thead>
                <tr>
                    <th class="code-column">Kode MK</th>
                    <th>Mata Kuliah</th>
                    <th class="grade-column">Nilai Huruf</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($targetCourses as $index => $course)

                    @php
                        $record = $records->get($course);
                    @endphp

                    <tr>
                        <td class="code-column">
                            MK{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                        </td>

                        <td>
                            {{ $course }}
                        </td>

                        <td class="grade-column">
                            {{ $record?->grade_letter ?? '—' }}
                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="4" class="empty-row">
                            Daftar mata kuliah belum tersedia.
                        </td>
                    </tr>

                @endforelse
            </tbody>

        </table>
    </div>

    <p class="academic-note">
        Nilai ditampilkan setelah dokumen berhasil diproses.
    </p>

</section>


<style>

/* PANEL UTAMA */

.academic-panel {
    min-height: 500px;
    padding: 22px 23px 28px;
}

/* HEADER PANEL */

.academic-panel-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
}

.academic-panel-heading h2 {
    margin: 0;
    color: #1b2f53;
    font-size: 17px;
    font-weight: 700;
}

.academic-panel-heading span {
    color: #71809a;
    font-size: 12px;
    white-space: nowrap;
}

/* TABEL AKADEMIK */

.academic-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.academic-table {
    width: 100%;
    border-collapse: collapse;
    color: #26364f;
    font-size: 12px;
}

.academic-table thead {
    background: #e8f2ff;
}

.academic-table th {
    height: 42px;
    padding: 0 16px;
    color: #1b2f53;
    font-size: 12px;
    font-weight: 700;
    text-align: left;
    white-space: nowrap;
}

.academic-table thead th:first-child {
    border-radius: 4px 0 0 4px;
}

.academic-table thead th:last-child {
    border-radius: 0 4px 4px 0;
}

/* ISI TABEL */

.academic-table td {
    height: 55px;
    padding: 12px 16px;
    border-bottom: 1px solid #d8e1eb;
    vertical-align: middle;
}

.academic-table .code-column {
    width: 120px;
}

.academic-table .grade-column {
    width: 130px;
}

.academic-table .numeric-column {
    width: 220px;
}

.academic-table td.grade-column,
.academic-table td.numeric-column {
    color: #71809a;
}

/* DATA KOSONG */

.academic-table .empty-row {
    height: 80px;
    color: #71809a;
    text-align: center;
}

/* KETERANGAN */

.academic-note {
    margin: 48px 0 0;
    color: #71809a;
    font-size: 12px;
}

/* RESPONSIVE */

@media (max-width: 760px) {

    .academic-panel {
        min-height: 0;
        padding: 18px;
    }

    .academic-panel-heading {
        margin-bottom: 18px;
    }

    .academic-table {
        min-width: 620px;
    }

    .academic-note {
        margin-top: 28px;
    }

}

</style>

@endsection
