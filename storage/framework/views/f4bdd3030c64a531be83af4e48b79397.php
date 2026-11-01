
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo $__env->yieldContent('title', 'SPK Kompetensi Mahasiswa'); ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #1b2f53;
            background: #f5f9fd;
        }

        a {
            text-decoration: none;
        }

        .topbar {
            height: 64px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border-bottom: 1px solid #dce4ee;
        }

        .brand {
            color: #1b2f53;
            font-size: 16px;
            font-weight: 700;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 13px;
        }

        .logout-button {
            border: 0;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            font-size: 13px;
        }

        .layout {
            display: flex;
            min-height: calc(100vh - 64px);
        }

        .sidebar {
            width: 236px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            padding: 34px 14px 20px;
            background: #fff;
            border-right: 1px solid #dce4ee;
        }

        .nav-link {
            display: block;
            padding: 13px 16px;
            margin-bottom: 7px;
            border-radius: 9px;
            color: #71809a;
            font-size: 13px;
        }

        .nav-link:hover,
        .nav-link.active {
            color: #3269ed;
            background: #e8f2ff;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid #dce4ee;
        }

        .main-content {
            flex: 1;
            min-width: 0;
            padding: 28px 34px;
        }

        .page-heading {
            margin-bottom: 28px;
        }

        .page-heading h1 {
            margin: 0 0 12px;
            font-size: 25px;
        }

        .page-heading p {
            margin: 0;
            color: #71809a;
            font-size: 14px;
        }

        .panel {
            padding: 24px;
            background: #fff;
            border: 1px solid #d8e1eb;
            border-radius: 12px;
        }

        .button-primary {
            display: inline-block;
            padding: 12px 18px;
            border: 0;
            border-radius: 8px;
            background: #3269ed;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
        }

        .button-primary:hover {
            background: #2558d4;
        }

        .form-control {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #d6dfeb;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            font-size: 13px;
        }

        .alert-error {
            padding: 12px;
            margin-bottom: 16px;
            border: 1px solid #ffd0cc;
            border-radius: 8px;
            background: #fff0f0;
            color: #b42318;
        }

        .alert-success {
            padding: 12px;
            margin-bottom: 16px;
            border: 1px solid #b7e4c7;
            border-radius: 8px;
            background: #effaf3;
            color: #216e39;
        }

        @media (max-width: 760px) {
            .topbar {
                padding: 0 16px;
            }

            .brand {
                font-size: 13px;
            }

            .layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 12px;
                border-right: 0;
                border-bottom: 1px solid #dce4ee;
            }

            .sidebar-bottom {
                margin-top: 8px;
            }

            .main-content {
                padding: 24px 16px;
            }

            .panel {
                padding: 18px;
            }
        }
    </style>
</head>

<body>
    <header class="topbar">
        <a href="<?php echo e(url('/dashboard')); ?>" class="brand">
            SPK KOMPETENSI MAHASISWA
        </a>

        <div class="user-menu">
            <?php if(auth()->guard()->check()): ?>
                <span><?php echo e(auth()->user()->name); ?></span>

                <form action="<?php echo e(url('/logout')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="logout-button">
                        Logout
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <nav>
                <a href="<?php echo e(url('/dashboard')); ?>"
                   class="nav-link <?php echo $__env->yieldContent('nav-dashboard'); ?>">
                    Dashboard
                </a>

                <a href="<?php echo e(url('/khs')); ?>"
                   class="nav-link <?php echo $__env->yieldContent('nav-khs'); ?>">
                    Unggah Dokumen
                </a>

                <a href="<?php echo e(url('/academic')); ?>"
                   class="nav-link <?php echo $__env->yieldContent('nav-academic'); ?>">
                    Data Akademik
                </a>

                <a href="<?php echo e(url('/recommendations')); ?>"
                   class="nav-link <?php echo $__env->yieldContent('nav-recommendations'); ?>">
                    Hasil Rekomendasi
                </a>
            </nav>

            <div class="sidebar-bottom">
                <?php if(auth()->guard()->check()): ?>
                    <form action="<?php echo e(url('/logout')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="logout-button">
                            Keluar / Logout
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </aside>

        <main class="main-content">
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>
</body>
</html>
<?php /**PATH C:\laragon\www\KHS-main\resources\views/layouts/app.blade.php ENDPATH**/ ?>