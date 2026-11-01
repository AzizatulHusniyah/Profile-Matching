## Instalasi dan Menjalankan Aplikasi

1. Clone repository:

```bash
git clone https://github.com/tifaniharista/KHS.git
```

2. Masuk ke folder project:

```bash
cd KHS
```

3. Instal dependensi:

```bash
composer install
```

4. Buat file `.env`:

```bash
cp .env.example .env
```

5. Generate application key:

```bash
php artisan key:generate
```

6. Buat database MySQL dengan nama `spk_kompetensi`, kemudian sesuaikan konfigurasi database pada file `.env`.

7. Jalankan migration:

```bash
php artisan migrate
```

8. Jalankan aplikasi:

```bash
php artisan serve
```

9. Buka aplikasi melalui browser:

```text
http://127.0.0.1:8000
```
