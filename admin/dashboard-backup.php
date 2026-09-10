<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';

requireLogin();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Sjoerd van der Veen</title>
</head>
<body>
    <h1>Dashboard</h1>

    <p>Welkom, <?php echo htmlspecialchars($_SESSION['name']); ?>.</p>
    <p>Rol: <?php echo htmlspecialchars($_SESSION['role']); ?></p>

    <hr>

    <h2>Beheer</h2>

    <ul>
        <li><a href="werkzaamheden.php">Werkzaamheden beheren</a></li>
        <li><a href="projecten.php">Projecten beheren</a></li>
        <li><a href='tiktok.php'>TikTok-video's beheren</a></li>
        <li><a href="boekingen.php">Boekingen bekijken</a></li>
        <li><a href="agenda.php">Agenda beheren</a></li>
    </ul>

    <?php if ($_SESSION['role'] === 'admin'): ?>
        <h2>Admin opties</h2>
        <ul>
            <li>Gebruikers beheren</li>
            <li>Systeeminstellingen</li>
        </ul>
    <?php endif; ?>

    <p>
        <a href="logout.php">Uitloggen</a>
    </p>
</body>
</html>
