<?php $__env->startSection('title', 'Hasil Rekomendasi | SPK Kompetensi Mahasiswa'); ?>
<?php $__env->startSection('nav-recommendations', 'active'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-heading">
    <h1>Hasil Rekomendasi Bidang Kerja</h1>
    <p>Peringkat berdasarkan hasil perhitungan Profile Matching.</p>
</div>

<?php if(session('success')): ?>
    <div class="alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>

<?php
    $ranking = $result ? collect($result->ranking_results ?? [])->sortBy('Ranking')->values() : collect();
    $topRecommendation = $ranking->first();
    // These labels are shown only as placeholders before a student's first calculation.
    $placeholderFields = [
        'Web Development',
        'Data Analyst',
        'Database',
        'IT Support',
        'Bidang kerja lainnya',
    ];
?>

<section class="panel recommendation-highlight">
    <div class="recommendation-icon">TOP</div>
    <div class="recommendation-copy">
        <div class="recommendation-label">Rekomendasi bidang kerja</div>
        <?php if($topRecommendation): ?>
            <h2><?php echo e($topRecommendation['Bidang Pekerjaan']); ?></h2>
            <p>Nilai akhir:
                <strong><?php echo e(number_format(((float) $topRecommendation['Nilai Akhir'] / 5) * 100, 2, ',', '.')); ?>%</strong>
                · Peringkat <?php echo e($topRecommendation['Ranking']); ?>

            </p>
        <?php else: ?>
            <h2>Belum tersedia</h2>
            <p>Unggah dan proses KHS untuk memperoleh hasil.</p>
        <?php endif; ?>
    </div>
</section>

<section class="panel ranking-panel">
    <h2>Daftar Peringkat Bidang Kerja</h2>

    <div class="ranking-table-wrap">
        <table class="ranking-table">
            <thead>
                <tr>
                    <th class="rank-column">Peringkat</th>
                    <th>Bidang Kerja</th>
                    <th class="score-column">Nilai Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if($ranking->isNotEmpty()): ?>
                    <?php $__currentLoopData = $ranking; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="rank-column">
                                <span class="rank-number"><?php echo e($row['Ranking']); ?></span>
                            </td>
                            <td><?php echo e($row['Bidang Pekerjaan']); ?></td>
                            <td class="score-column">
                                <strong><?php echo e(number_format(((float) $row['Nilai Akhir'] / 5) * 100, 2, ',', '.')); ?>%</strong>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                    <?php $__currentLoopData = $placeholderFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="rank-column muted-dash">—</td>
                            <td><?php echo e($field); ?></td>
                            <td class="score-column muted-dash">—</td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (! ($result)): ?>
        <div class="ranking-note">
            Hasil peringkat akan ditampilkan setelah enam dokumen KHS berhasil diunggah dan divalidasi.
            <a href="<?php echo e(route('khs.create')); ?>" class="button-primary ranking-upload-button">Unggah Dokumen KHS</a>
        </div>
    <?php endif; ?>
</section>

<style>
    .recommendation-highlight {
        min-height: 150px;
        display: flex;
        align-items: flex-start;
        gap: 26px;
        padding: 24px;
        margin-bottom: 25px;
    }

    .recommendation-icon {
        width: 70px;
        height: 70px;
        flex: 0 0 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        background: #e8f2ff;
        color: #3269ed;
        font-size: 16px;
        font-weight: 700;
    }

    .recommendation-copy {
        min-width: 0;
        padding-top: 0;
    }

    .recommendation-label {
        margin-bottom: 10px;
        color: #71809a;
        font-size: 12px;
    }

    .recommendation-copy h2 {
        margin: 0 0 10px;
        color: #1b2f53;
        font-size: 23px;
        line-height: 1.25;
    }

    .recommendation-copy p {
        margin: 0;
        color: #71809a;
        font-size: 13px;
        line-height: 1.6;
    }

    .recommendation-copy p strong {
        color: #1b2f53;
    }

    .chart-panel {
        margin-bottom: 25px;
    }

    .chart-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 22px;
    }

    .chart-heading h2 {
        margin: 0 0 7px;
        color: #1b2f53;
        font-size: 17px;
    }

    .chart-heading p {
        margin: 0;
        color: #71809a;
        font-size: 12px;
        line-height: 1.5;
    }

    .chart-scale {
        flex: 0 0 auto;
        padding: 6px 10px;
        border-radius: 6px;
        background: #f1f6fc;
        color: #71809a;
        font-size: 11px;
    }

    .score-chart {
        display: flex;
        flex-direction: column;
        gap: 17px;
    }

    .chart-row {
        display: grid;
        grid-template-columns: minmax(145px, 220px) minmax(100px, 1fr) 72px;
        align-items: center;
        gap: 14px;
    }

    .chart-label {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
        color: #26364f;
        font-size: 12px;
    }

    .chart-label > span:last-child {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .chart-rank {
        flex: 0 0 auto;
        color: #71809a;
        font-size: 11px;
        font-weight: 700;
    }

    .chart-track {
        height: 13px;
        overflow: hidden;
        border-radius: 20px;
        background: #edf2f8;
    }

    .chart-bar {
        height: 100%;
        min-width: 2px;
        border-radius: 20px;
        background: #8db6f5;
        transition: width .35s ease;
    }

    .chart-bar-top {
        background: #3269ed;
    }

    .chart-value {
        color: #1b2f53;
        font-size: 12px;
        font-weight: 700;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .chart-footnote {
        margin-top: 20px;
        color: #8a97aa;
        font-size: 11px;
    }

    .chart-empty {
        padding: 25px 16px;
        border: 1px dashed #d8e1eb;
        border-radius: 8px;
        color: #71809a;
        font-size: 12px;
        text-align: center;
        line-height: 1.6;
    }

    .ranking-panel h2 {
        margin: 0 0 20px;
        color: #1b2f53;
        font-size: 17px;
    }

    .ranking-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .ranking-table {
        width: 100%;
        border-collapse: collapse;
        color: #26364f;
        font-size: 12px;
    }

    .ranking-table thead {
        background: #e8f2ff;
    }

    .ranking-table th {
        height: 43px;
        padding: 0 16px;
        color: #1b2f53;
        font-weight: 700;
        text-align: left;
        white-space: nowrap;
    }

    .ranking-table thead th:first-child {
        border-radius: 4px 0 0 4px;
    }

    .ranking-table thead th:last-child {
        border-radius: 0 4px 4px 0;
    }

    .ranking-table td {
        height: 32px;
        padding: 8px 16px;
        border-bottom: 1px solid #d8e1eb;
        vertical-align: middle;
    }

    .ranking-table .rank-column {
        width: 150px;
        text-align: left;
    }

    .ranking-table .score-column {
        width: 220px;
        text-align: center;
    }

    .rank-number {
        display: inline-flex;
        min-width: 22px;
        min-height: 22px;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        background: #e8f2ff;
        color: #3269ed;
        font-weight: 700;
    }

    .muted-dash {
        color: #71809a;
        text-align: center !important;
    }

    .ranking-note {
        margin-top: 18px;
        color: #71809a;
        font-size: 12px;
        line-height: 1.6;
    }

    .ranking-upload-button {
        margin-top: 12px;
        padding: 9px 14px;
        font-size: 12px;
    }

    @media (max-width: 760px) {
        .recommendation-highlight {
            gap: 16px;
            padding: 18px;
        }

        .recommendation-icon {
            width: 58px;
            height: 58px;
            flex-basis: 58px;
        }

        .recommendation-copy h2 {
            font-size: 19px;
        }

        .chart-row {
            grid-template-columns: minmax(105px, 135px) minmax(60px, 1fr) 58px;
            gap: 8px;
        }

        .chart-label,
        .chart-value {
            font-size: 10px;
        }

        .chart-rank {
            font-size: 10px;
        }

        .chart-heading {
            flex-direction: column;
        }

        .ranking-table .rank-column {
            width: 90px;
        }

        .ranking-table .score-column {
            width: 130px;
        }

        .ranking-table th,
        .ranking-table td {
            padding-left: 10px;
            padding-right: 10px;
        }
    }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\spkver1\resources\views/recommendations/index.blade.php ENDPATH**/ ?>