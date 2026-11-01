
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | SPK Kompetensi Mahasiswa</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #1b2f53;
            background: #fff;
        }

        .login-page {
            display: grid;
            grid-template-columns: 41.7% 58.3%;
            min-height: 100vh;
        }

        .login-brand {
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

        .login-content {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
        }

        .login-form {
            width: 100%;
            max-width: 500px;
        }

        .login-form h1 {
            font-size: 30px;
            margin: 0 0 14px;
        }

        .subtitle {
            color: #7b89a1;
            margin: 0 0 52px;
            font-size: 15px;
        }

        .form-group {
            margin-bottom: 24px;
        }

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

        input::placeholder {
            color: #7b89a1;
        }

        .login-button {
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

        .login-button:hover {
            background: #2558d4;
        }

        .login-note {
            color: #7b89a1;
            font-size: 13px;
            margin: 26px 0 12px;
            text-align: center;
        }

        .register-button {
            display: flex;
            width: 100%;
            height: 54px;
            align-items: center;
            justify-content: center;
            border: 1px solid #3269ed;
            border-radius: 9px;
            background: #fff;
            color: #3269ed;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background .2s ease, color .2s ease;
        }

        .register-button:hover {
            background: #e8f2ff;
            color: #2558d4;
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
            .login-page {
                grid-template-columns: 1fr;
            }

            .login-brand {
                padding: 24px;
            }

            .brand-card {
                min-height: auto;
                padding: 30px 24px;
            }

            .brand-card h2 {
                font-size: 22px;
                margin-bottom: 12px;
            }

            .brand-card h3 {
                margin-bottom: 8px;
            }

            .login-content {
                padding: 36px 24px;
            }

            .login-form h1 {
                font-size: 27px;
            }

            .subtitle {
                margin-bottom: 32px;
            }
        }
    </style>
</head>
<body>
    <main class="login-page">
        <section class="login-brand">
            <div class="brand-card">
                <h2>SPK Kompetensi<br>Mahasiswa</h2>
                <h3>D4 Manajemen Informatika</h3>
                <p>
                    Rekomendasi bidang kerja berdasarkan
                    capaian akademik mahasiswa.
                </p>
            </div>
        </section>

        <section class="login-content">
            <div class="login-form">
                <h1>Selamat Datang</h1>
                <p class="subtitle">
                    Masuk untuk melanjutkan ke aplikasi
                </p>

                @if ($errors->any())
                    <div class="error-message" role="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ url('/login') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Masukkan email"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <button type="submit" class="login-button">
                        Login
                    </button>
                </form>

                <p class="login-note">
                    Belum memiliki akun?
                </p>

                <a href="{{ route('register') }}" class="register-button">
                    Register
                </a>
            </div>
        </section>
    </main>
</body>
</html>
