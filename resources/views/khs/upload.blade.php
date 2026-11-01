@extends('layouts.app')

@section('title', 'Pemrosesan Dokumen KHS | SPK Kompetensi Mahasiswa')
@section('nav-khs', 'active')

@section('content')
    <style>
        .processing-wrap { max-width: 1020px; }
        .page-heading { margin-bottom: 32px; }
        .page-heading h1 { font-size: 25px; }
        .document-card {
            display: flex; align-items: center; gap: 16px; padding: 20px 21px;
            margin-bottom: 24px; background: #fff; border: 1px solid #d8e1eb;
            border-radius: 12px;
        }
        .document-icon {
            display: grid; place-items: center; flex: 0 0 52px; height: 54px;
            border-radius: 9px; background: #e8f1ff; color: #3269ed; font-size: 23px;
        }
        .document-info { flex: 1; min-width: 0; }
        .document-name { color: #1b2f53; font-size: 16px; font-weight: 700; }
        .document-meta { margin-top: 7px; color: #71809a; font-size: 12px; }
        .status-pill {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 160px; padding: 8px 15px; border-radius: 999px;
            background: #eaf7ee; color: #217346; font-size: 12px; font-weight: 600;
        }
        .status-pill.invalid { background: #fff0f0; color: #b42318; }
        .result-card {
            padding: 23px; background: #fff; border: 1px solid #d8e1eb;
            border-radius: 12px;
        }
        .result-card h2 { margin: 0 0 12px; color: #1b2f53; font-size: 17px; }
        .result-row {
            display: grid; grid-template-columns: 30% 1fr; gap: 16px;
            padding: 14px 6px; border-bottom: 1px solid #d8e1eb; font-size: 13px;
        }
        .result-label { color: #71809a; }
        .result-value { color: #24334d; font-weight: 500; }
        .result-note { margin: 27px 0 0; color: #71809a; font-size: 12px; }
        .result-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 20px; }
        .button-secondary {
            display: inline-block; padding: 11px 16px; border: 1px solid #d6dfeb;
            border-radius: 8px; background: #fff; color: #52627b; cursor: pointer; font-size: 13px;
        }
        .alert-success, .alert-error { margin-bottom: 18px; }
        .upload-panel { max-width: 920px; }
        .dropzone {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 245px; padding: 28px; border: 2px dashed #9aafd0; border-radius: 14px;
            background: #f8fbff; text-align: center; transition: .15s ease; cursor: pointer;
        }
        .dropzone.is-dragging { border-color: #3269ed; background: #edf4ff; transform: scale(1.005); }
        .dropzone-icon { display: grid; place-items: center; width: 60px; height: 60px; margin-bottom: 14px; border-radius: 16px; background: #e8f0ff; color: #3269ed; font-size: 28px; }
        .dropzone h2 { margin: 0 0 8px; font-size: 19px; }
        .dropzone p { max-width: 520px; margin: 0 0 15px; color: #71809a; font-size: 13px; line-height: 1.6; }
        .file-guidance { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; color: #64748b; font-size: 12px; }
        .file-guidance span { padding: 5px 9px; border-radius: 999px; background: #eef3f9; }
        .file-list { display: grid; gap: 9px; margin: 18px 0; padding: 0; list-style: none; }
        .file-item { display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid #dce4ee; border-radius: 10px; }
        .file-icon { display: grid; place-items: center; flex: 0 0 40px; height: 40px; border-radius: 9px; background: #edf4ff; color: #3269ed; font-weight: 700; font-size: 11px; }
        .file-info { flex: 1; min-width: 0; }
        .file-name { overflow-wrap: anywhere; font-size: 13px; font-weight: 700; color: #1b2f53; }
        .file-meta { margin-top: 4px; color: #71809a; font-size: 12px; }
        .file-remove { border: 0; border-radius: 7px; padding: 8px 10px; background: #fff0f0; color: #b42318; cursor: pointer; }
        .upload-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 18px; }
        .upload-status { color: #64748b; font-size: 13px; }
        .upload-status.is-ready { color: #217346; font-weight: 700; }
        .button-primary:disabled { cursor: not-allowed; opacity: .55; }
        @media (max-width: 600px) {
            .document-card { align-items: flex-start; flex-wrap: wrap; }
            .status-pill { margin-left: 68px; }
            .result-card { padding: 18px; }
            .result-row { grid-template-columns: 1fr; gap: 6px; }
        }
    </style>

    @if (isset($documents) && $documents->count() > 0)
        @php
            $isValid = $documents->count() === 6 && $documents->every(fn ($document) => $document->status === 'processed');
        @endphp
        <div class="processing-wrap">
            <div class="page-heading">
                <h1>Pemrosesan Dokumen KHS</h1>
                <p>Pemeriksaan kesesuaian dokumen sebelum diproses.</p>
            </div>

            @if (session('success'))
                <div class="alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif

            <section class="document-card" aria-label="Ringkasan dokumen KHS">
                <div class="document-icon" aria-hidden="true">▤</div>
                <div class="document-info">
                    <div class="document-name">Paket Dokumen KHS</div>
                    <div class="document-meta">PDF • {{ $documents->count() }} dokumen • Semester 1–6</div>
                </div>
                <span class="status-pill {{ $isValid ? '' : 'invalid' }}">
                    {{ $isValid ? 'Sesuai' : 'Tidak sesuai' }}
                </span>
            </section>

            <section class="result-card">
                <h2>Hasil Pemeriksaan Dokumen</h2>
                <div class="result-row">
                    <span class="result-label">Format file</span>
                    <span class="result-value">PDF</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Cakupan semester</span>
                    <span class="result-value">Semester 1–6</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Nama mahasiswa</span>
                    <span class="result-value">{{ $documents->first()?->student_name ?: 'Belum tersedia' }}</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Jenis dokumen</span>
                    <span class="result-value">KHS D4 Manajemen Informatika</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Status pemeriksaan</span>
                    <span class="result-value">{{ $isValid ? 'Sesuai' : 'Tidak sesuai' }}</span>
                </div>
                <p class="result-note">
                    {{ $isValid
                        ? 'Dokumen berhasil divalidasi dan data akademik telah diproses. Kamu dapat melanjutkan untuk melihat hasil rekomendasi.'
                        : 'Dokumen belum memenuhi seluruh ketentuan. Periksa kembali dokumen yang diunggah.' }}
                </p>
                <div class="result-actions">
                    @if ($isValid)
                        <a href="{{ route('recommendations.index') }}" class="button-primary">Lihat Hasil Rekomendasi</a>
                    @endif
                    <form action="{{ route('khs.destroy') }}" method="POST"
                        onsubmit="return confirm('Hapus seluruh unggahan KHS, data akademik, dan hasil rekomendasi milik akunmu? Tindakan ini tidak dapat dibatalkan.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="button-secondary">Unggah Ulang Dokumen</button>
                    </form>
                </div>
            </section>
        </div>
    @else
        <div class="page-heading">
            <h1>Unggah Dokumen KHS</h1>
            <p>Unggah enam dokumen KHS semester 1 sampai 6 sekaligus. Urutan file bebas karena semester dibaca dari isi PDF.</p>
        </div>

        <section class="panel upload-panel">
            @if (session('success'))
                <div class="alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert-error">
                    <strong>Unggahan belum dapat diproses:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p style="margin-top:0; color:#52627b; font-size:14px; line-height:1.6;">
                Pilih atau seret tepat enam PDF KHS. Sistem akan memeriksa semester 1–6, file duplikat, dan cakupan 26 mata kuliah target sebelum menyimpan data.
            </p>
            <form id="khs-upload-form" action="{{ route('khs.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input id="khs-files" type="file" name="documents[]" accept=".pdf,application/pdf" multiple hidden>
                <div id="khs-dropzone" class="dropzone" role="button" tabindex="0" aria-label="Pilih atau seret file PDF KHS">
                    <div class="dropzone-icon" aria-hidden="true">↑</div>
                    <h2>Seret dan lepaskan file PDF KHS di sini</h2>
                    <p>Atau pilih file dari komputer. Kamu bisa memilih keenam file sekaligus, tanpa perlu mengurutkannya berdasarkan semester.</p>
                    <span class="button-primary" aria-hidden="true">Pilih file PDF</span>
                    <div class="file-guidance" style="margin-top:14px;">
                        <span>PDF saja</span><span>Maks. 10 MB per file</span><span>Tepat 6 file</span>
                    </div>
                </div>
                <div id="khs-file-summary" class="upload-status" style="margin-top:16px;" aria-live="polite">Belum ada file yang dipilih.</div>
                <ul id="khs-file-list" class="file-list" aria-label="Daftar file yang dipilih"></ul>
                <div class="upload-actions">
                    <button id="khs-submit" type="submit" class="button-primary" disabled>Validasi dan proses 6 KHS</button>
                    <span id="khs-submit-help" class="upload-status">Pilih tepat enam PDF untuk melanjutkan.</span>
                </div>
            </form>
        </section>
    @endif

    @if (!isset($documents) || $documents->count() === 0)
        <script>
            (() => {
                const input = document.getElementById('khs-files');
                const dropzone = document.getElementById('khs-dropzone');
                const list = document.getElementById('khs-file-list');
                const summary = document.getElementById('khs-file-summary');
                const submit = document.getElementById('khs-submit');
                const help = document.getElementById('khs-submit-help');
                const form = document.getElementById('khs-upload-form');
                if (!input || !dropzone || !list || !summary || !submit || !help || !form) return;

                let selectedFiles = [];
                const maxBytes = 10 * 1024 * 1024;
                const formatSize = (bytes) => bytes < 1024 * 1024
                    ? `${Math.max(1, Math.round(bytes / 1024))} KB`
                    : `${(bytes / (1024 * 1024)).toFixed(2)} MB`;

                function fileKey(file) {
                    return `${file.name}:${file.size}:${file.lastModified}`;
                }

                function syncInput() {
                    const transfer = new DataTransfer();
                    selectedFiles.forEach(file => transfer.items.add(file));
                    input.files = transfer.files;
                }

                function render() {
                    list.replaceChildren();
                    const count = selectedFiles.length;
                    const invalid = selectedFiles.filter(file => !/\.pdf$/i.test(file.name) || file.size > maxBytes);
                    summary.textContent = count === 0
                        ? 'Belum ada file yang dipilih.'
                        : `${count} dari 6 file dipilih${invalid.length ? ` · ${invalid.length} file tidak memenuhi ketentuan` : count < 6 ? ' · Pilih atau tambahkan file lagi untuk melengkapi enam dokumen.' : '.'}`;
                    summary.classList.toggle('is-ready', count === 6 && invalid.length === 0);
                    selectedFiles.forEach((file, index) => {
                        const item = document.createElement('li');
                        item.className = 'file-item';
                        const icon = document.createElement('div');
                        icon.className = 'file-icon';
                        icon.textContent = 'PDF';
                        const info = document.createElement('div');
                        info.className = 'file-info';
                        const name = document.createElement('div');
                        name.className = 'file-name';
                        name.textContent = file.name;
                        const meta = document.createElement('div');
                        meta.className = 'file-meta';
                        const issues = [];
                        if (!/\.pdf$/i.test(file.name)) issues.push('bukan PDF');
                        if (file.size > maxBytes) issues.push('lebih dari 10 MB');
                        meta.textContent = `${formatSize(file.size)}${issues.length ? ` · ${issues.join(', ')}` : ''}`;
                        info.append(name, meta);
                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'file-remove';
                        remove.textContent = 'Hapus';
                        remove.setAttribute('aria-label', `Hapus ${file.name}`);
                        remove.addEventListener('click', () => {
                            selectedFiles.splice(index, 1);
                            syncInput();
                            render();
                        });
                        item.append(icon, info, remove);
                        list.append(item);
                    });
                    const ready = count === 6 && invalid.length === 0;
                    submit.disabled = !ready;
                    help.textContent = ready
                        ? 'Siap diunggah. Nama mahasiswa dan semester akan diperiksa dari isi setiap PDF.'
                        : 'Pilih tepat enam PDF, masing-masing maksimal 10 MB.';
                }

                function addFiles(fileList) {
                    const incoming = Array.from(fileList || []);
                    const merged = [...selectedFiles];
                    incoming.forEach(file => {
                        if (!merged.some(existing => fileKey(existing) === fileKey(file))) merged.push(file);
                    });
                    selectedFiles = merged;
                    if (selectedFiles.length > 6) {
                        summary.textContent = 'Terlalu banyak file. Pilih tepat enam PDF.';
                    }
                    syncInput();
                    render();
                }

                dropzone.addEventListener('click', () => input.click());
                dropzone.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        input.click();
                    }
                });
                input.addEventListener('change', () => {
                    // Keep files selected in earlier picker sessions.
                    // The browser's FileList contains only the latest selection,
                    // while selectedFiles is the persistent in-page collection.
                    addFiles(input.files);
                });
                ['dragenter', 'dragover'].forEach(type => dropzone.addEventListener(type, event => {
                    event.preventDefault();
                    dropzone.classList.add('is-dragging');
                }));
                ['dragleave', 'drop'].forEach(type => dropzone.addEventListener(type, event => {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragging');
                }));
                dropzone.addEventListener('drop', event => addFiles(event.dataTransfer.files));
                form.addEventListener('submit', event => {
                    if (selectedFiles.length !== 6 || selectedFiles.some(file => !/\.pdf$/i.test(file.name) || file.size > maxBytes)) {
                        event.preventDefault();
                        render();
                        return;
                    }
                    submit.disabled = true;
                    submit.textContent = 'Memvalidasi dokumen…';
                });
                render();
            })();
        </script>
    @endif
@endsection
