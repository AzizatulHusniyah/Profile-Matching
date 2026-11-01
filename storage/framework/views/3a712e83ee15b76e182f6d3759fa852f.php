<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi | SPK Kompetensi Mahasiswa</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #1b2f53;
            background: #fff;
        }

        .register-page {
            display: grid;
            grid-template-columns: 41.7% 58.3%;
            min-height: 100vh;
        }

        .register-brand {
            background: #e8f2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .brand-card {
            width: 100%;
            max-width: 390px;
            min-height: 530px;
            padding: 72px 38px;
            background: #fff;
            border-radius: 22px;
            text-align: center;
        }

        .brand-card h2 {
            font-size: 24px;
            line-height: 1.25;
            margin: 0 0 22px;
        }

        .brand-card h3 {
            color: #70809b;
            font-size: 16px;
            font-weight: 400;
            margin-bottom: 16px;
        }

        .brand-card p {
            color: #70809b;
            font-size: 14px;
            line-height: 1.5;
        }

        .register-content {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
        }

        .register-form {
            width: 100%;
            max-width: 500px;
        }

        .register-form h1 {
            font-size: 30px;
            margin: 0 0 14px;
        }

        .subtitle {
            color: #7b89a1;
            margin: 0 0 38px;
            font-size: 15px;
        }

        .form-group { margin-bottom: 22px; }

        label {
            display: block;
            font-size: 13px;
            margin-bottom: 10px;
            color: #25334b;
        }

        input {
            width: 100%;
            height: 54px;
            border: 1px solid #d6dfeb;
            border-radius: 9px;
            padding: 0 17px;
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: #3269ed;
            box-shadow: 0 0 0 3px rgba(50, 105, 237, .10);
        }

        input::placeholder { color: #7b89a1; }

        .register-button {
            width: 100%;
            height: 54px;
            border: 0;
            border-radius: 9px;
            background: #3269ed;
            color: white;
            font-weight: 600;
            cursor: pointer;
            margin-top: 4px;
        }

        .register-button:hover { background: #2558d4; }

        .register-note {
            color: #7b89a1;
            font-size: 13px;
            margin-top: 25px;
            text-align: center;
        }

        .register-note a {
            color: #3269ed;
            font-weight: 600;
            text-decoration: none;
        }

        .error-message {
            background: #fff0f0;
            color: #b42318;
            border: 1px solid #ffd0cc;
            border-radius: 8px;
            padding: 12px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        @media (max-width: 760px) {
            .register-page { grid-template-columns: 1fr; }
            .register-brand { padding: 24px; }
            .brand-card { min-height: auto; padding: 30px 24px; }
            .brand-card h2 { font-size: 22px; margin-bottom: 12px; }
            .brand-card h3 { margin-bottom: 8px; }
            .register-content { padding: 36px 24px; }
            .register-form h1 { font-size: 27px; }
            .subtitle { margin-bottom: 30px; }
        }
    </style>
</head>
<body>
    <main class="register-page">
        <section class="register-brand">
            <div class="brand-card">
                <h2>SPK Kompetensi<br>Mahasiswa</h2>
                <h3>D4 Manajemen Informatika</h3>
                <p>
                    Rekomendasi bidang kerja berdasarkan
                    capaian akademik mahasiswa.
                </p>
            </div>
        </section>

        <section class="register-content">
            <div class="register-form">
                <h1>Registrasi Mahasiswa</h1>
                <p class="subtitle">
                    Buat akun untuk menggunakan aplikasi SPK Kompetensi Mahasiswa.
                </p>

                <?php if($errors->any()): ?>
                    <div class="error-message" role="alert">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div><?php echo e($error); ?></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e(route('register.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>

                    <div class="form-group">
                        <label for="name">Nama Mahasiswa</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php echo e(old('name')); ?>"
                            placeholder="Masukkan nama lengkap"
                            autocomplete="name"
                            maxlength="255"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo e(old('email')); ?>"
                            placeholder="Masukkan email aktif"
                            autocomplete="email"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minimal 8 karakter"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                    </div>

                    <button type="submit" class="register-button">
                        Daftar
                    </button>
                </form>

                <p class="register-note">
                    Sudah memiliki akun?
                    <a href="<?php echo e(route('login')); ?>">Login di sini</a>
                </p>
            </div>
        </section>
    </main>
</body>
</html>
<?php /**PATH C:\laragon\www\spkver1\resources\views/auth/register.blade.php ENDPATH**/ ?>