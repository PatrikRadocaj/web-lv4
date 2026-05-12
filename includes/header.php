<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title ?? 'LV4 Videoteka') ?></title>
    <link rel="stylesheet" href="assets/lv4.css">
</head>
<body>
<header>
    <div class="header-top">
        <h1><?= h($title ?? 'LV4 Videoteka') ?></h1>
        <p class="podnaslov">PHP, MySQL, sesije i trajna pohrana podataka</p>
    </div>

    <div class="nav-wrapper">
        <input type="checkbox" id="menu-toggle" class="menu-toggle">

        <label for="menu-toggle" class="hamburger" aria-label="Otvori navigaciju">
            <span></span>
            <span></span>
            <span></span>
        </label>

        <nav aria-labelledby="primarna-navigacija" class="glavna-navigacija">
            <h2 id="primarna-navigacija">Primarna navigacija</h2>
            <ul>
                <li><a href="index.php">Početna</a></li>
                <li><a href="grafikon.php">Grafikon</a></li>
                <li><a href="gallery.php">Galerija</a></li>
                <li><a href="my_videoteka.php">Moja videoteka</a></li>
                <?php if (is_admin()): ?>
                    <li><a href="admin_films.php">Admin filmovi</a></li>
                <?php endif; ?>
                <?php if (is_logged_in()): ?>
                    <li><a href="logout.php">Odjava (<?= h(current_user()['username']) ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Prijava</a></li>
                    <li><a href="register.php">Registracija</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main>
<?php if ($message = flash()): ?>
    <p class="notice"><?= h($message) ?></p>
<?php endif; ?>
