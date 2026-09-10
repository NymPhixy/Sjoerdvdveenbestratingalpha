<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';
require_once '../app/includes/admin-menu.php';

requireLogin();

$userName = $_SESSION['name'] ?? 'Gebruiker';
$userRole = $_SESSION['role'] ?? '';

// Statistieken ophalen
$newBookingsStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM bookings 
    WHERE status = 'pending'
");
$newBookingsStmt->execute();
$newBookings = $newBookingsStmt->fetchColumn();

$confirmedBookingsStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM bookings 
    WHERE status = 'confirmed'
    AND booking_date >= CURDATE()
");
$confirmedBookingsStmt->execute();
$confirmedBookings = $confirmedBookingsStmt->fetchColumn();

$openSlotsStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM availability_slots 
    WHERE is_available = 1
    AND date >= CURDATE()
");
$openSlotsStmt->execute();
$openSlots = $openSlotsStmt->fetchColumn();

$projectsStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM projects 
    WHERE is_visible = 1
");
$projectsStmt->execute();
$projectsOnline = $projectsStmt->fetchColumn();

$servicesStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM services 
    WHERE is_visible = 1
");
$servicesStmt->execute();
$servicesOnline = $servicesStmt->fetchColumn();

$tiktokStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tiktok_videos 
    WHERE is_visible = 1
");
$tiktokStmt->execute();
$tiktokOnline = $tiktokStmt->fetchColumn();

// Laatste 5 boekingen
$latestBookingsStmt = $pdo->prepare("
    SELECT *
    FROM bookings
    ORDER BY created_at DESC
    LIMIT 5
");
$latestBookingsStmt->execute();
$latestBookings = $latestBookingsStmt->fetchAll(PDO::FETCH_ASSOC);

// Komende 5 bevestigde afspraken
$upcomingBookingsStmt = $pdo->prepare("
    SELECT *
    FROM bookings
    WHERE status = 'confirmed'
    AND booking_date >= CURDATE()
    ORDER BY booking_date ASC, start_time ASC
    LIMIT 5
");
$upcomingBookingsStmt->execute();
$upcomingBookings = $upcomingBookingsStmt->fetchAll(PDO::FETCH_ASSOC);

function statusLabel($status)
{
    switch ($status) {
        case 'pending':
            return 'In afwachting';
        case 'confirmed':
            return 'Bevestigd';
        case 'cancelled':
            return 'Geannuleerd';
        case 'completed':
            return 'Afgerond';
        default:
            return $status;
    }
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Sjoerd van der Veen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --dark: #101a16;
            --green: #243f32;
            --green-soft: #e9f3ea;
            --beige: #f7f4ef;
            --border: #e1dbd1;
            --muted: #6f746e;
            --gold: #b08a57;
            --danger: #9d2f2f;
            --white: #ffffff;
            --shadow: 0 24px 70px rgba(31, 42, 36, 0.10);
            --radius: 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f1;
            color: var(--dark);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .dashboard-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px;
        }

        .dashboard-frame {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 30px;
            box-shadow: var(--shadow);
            overflow: hidden;
            min-height: 780px;
        }

        .topbar {
            min-height: 76px;
            padding: 18px 28px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .brand {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.04em;
            color: var(--green);
            white-space: nowrap;
        }

        .topbar-center {
            flex: 1;
            max-width: 520px;
            background: var(--beige);
            border-radius: 999px;
            padding: 12px 18px;
            color: var(--muted);
            font-size: 14px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 11px 16px;
            font-weight: 800;
            border: 1px solid transparent;
            cursor: pointer;
            font-size: 14px;
        }

        .button.primary {
            background: var(--green);
            color: var(--white);
        }

        .button.secondary {
            background: var(--beige);
            color: var(--green);
            border-color: var(--border);
        }

        .dashboard-body {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 700px;
        }

        .sidebar {
            background: #f7f8f6;
            border-right: 1px solid var(--border);
            padding: 24px;
        }

        .profile-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 18px;
            margin-bottom: 24px;
        }

        .profile-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--green);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .profile-card strong {
            display: block;
            margin-bottom: 4px;
        }

        .profile-card span {
            color: var(--muted);
            font-size: 14px;
        }

        .nav-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--muted);
            font-weight: 900;
            margin: 20px 0 10px;
        }

        .admin-nav {
            display: grid;
            gap: 8px;
        }

        .admin-nav a {
            display: block;
            padding: 12px 14px;
            border-radius: 14px;
            color: var(--green);
            font-weight: 800;
            transition: 0.2s ease;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background: var(--green);
            color: var(--white);
        }

        .main {
            padding: 30px;
        }

        .welcome {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: flex-start;
            margin-bottom: 28px;
        }

        .welcome h1 {
            font-family: Georgia, serif;
            font-size: clamp(36px, 5vw, 58px);
            line-height: 1;
            letter-spacing: -0.06em;
            margin: 0 0 10px;
            color: var(--dark);
        }

        .welcome p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            max-width: 620px;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: 0 14px 40px rgba(31, 42, 36, 0.06);
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            right: -36px;
            top: -36px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--green-soft);
        }

        .stat-label {
            color: var(--muted);
            font-weight: 800;
            font-size: 14px;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }

        .stat-number {
            font-family: Georgia, serif;
            font-size: 46px;
            font-weight: 900;
            color: var(--green);
            letter-spacing: -0.06em;
            position: relative;
            z-index: 1;
        }

        .stat-note {
            color: var(--muted);
            font-size: 13px;
            margin-top: 8px;
            position: relative;
            z-index: 1;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 14px 40px rgba(31, 42, 36, 0.06);
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            margin-bottom: 18px;
        }

        .panel h2 {
            font-family: Georgia, serif;
            font-size: 30px;
            letter-spacing: -0.04em;
            margin: 0;
            color: var(--dark);
        }

        .small-link {
            color: var(--gold);
            font-weight: 900;
            font-size: 14px;
        }

        .booking-list {
            display: grid;
            gap: 12px;
        }

        .booking-item {
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 15px;
            background: #fffdfa;
            display: grid;
            gap: 8px;
        }

        .booking-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .booking-name {
            font-weight: 900;
            color: var(--green);
        }

        .booking-meta {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.confirmed {
            background: var(--green-soft);
            color: var(--green);
        }

        .badge.cancelled {
            background: #f8dfdf;
            color: var(--danger);
        }

        .badge.completed {
            background: #e6edf5;
            color: #24476b;
        }

        .empty-state {
            border: 1px dashed var(--border);
            background: var(--beige);
            color: var(--muted);
            border-radius: 18px;
            padding: 18px;
            line-height: 1.6;
        }

        @media (max-width: 1050px) {
            .dashboard-body {
                grid-template-columns: 1fr;
            }

            .sidebar {
                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .stats-grid,
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .welcome {
                flex-direction: column;
            }

            .topbar {
                height: auto;
                padding: 18px;
                flex-direction: column;
                align-items: flex-start;
            }

            .topbar-center {
                width: 100%;
                max-width: none;
            }

            .topbar-actions {
                flex-wrap: wrap;
            }
        }
  </style>

<link rel="stylesheet" href="../public/assets/css/admin-responsive.css">
</head>
<body>

<div class="dashboard-shell">
    <div class="dashboard-frame">

        <header class="topbar">
            <div class="brand">Sjoerd Dashboard</div>

            <div class="topbar-center">
                Beheer website, werkzaamheden, projecten, agenda en boekingen
            </div>

            <div class="topbar-actions">
                <a class="button secondary" href="../public/index.php" target="_blank">Website bekijken</a>
                <a class="button primary" href="logout.php">Uitloggen</a>
            </div>
        </header>

        <div class="dashboard-body">
            <aside class="sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>

                    <strong><?php echo htmlspecialchars($userName); ?></strong>
                    <span>Rol: <?php echo htmlspecialchars($userRole); ?></span>
                </div>

                <div class="nav-title">Beheer</div>

               <?php renderAdminMenu('dashboard'); ?>

                <div class="nav-title">Snel</div>

                <nav class="admin-nav">
                    <a href="agenda.php">Tijdslot toevoegen</a>
                    <a href="../public/afspraak.php" target="_blank">Boekingspagina testen</a>
                    <a href="../public/index.php#contact" target="_blank">Contactsectie bekijken</a>
                </nav>
            </aside>

            <main class="main">
                <section class="welcome">
                    <div>
                        <h1>Welkom terug, <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?></h1>
                        <p>
                            Hier zie je in één oogopslag wat er speelt rondom Sjoerds website:
                            nieuwe aanvragen, open tijdsloten, projecten, werkzaamheden en komende afspraken.
                        </p>
                    </div>

                    <div class="quick-actions">
                        <a class="button primary" href="agenda.php">Agenda beheren</a>
                        <a class="button secondary" href="boekingen.php">Boekingen bekijken</a>
                    </div>
                </section>

                <section class="stats-grid">
                    <article class="stat-card">
                        <div class="stat-label">Nieuwe aanvragen</div>
                        <div class="stat-number"><?php echo htmlspecialchars($newBookings); ?></div>
                        <div class="stat-note">Boekingen met status in afwachting</div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-label">Bevestigde afspraken</div>
                        <div class="stat-number"><?php echo htmlspecialchars($confirmedBookings); ?></div>
                        <div class="stat-note">Komende bevestigde afspraken</div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-label">Open tijdsloten</div>
                        <div class="stat-number"><?php echo htmlspecialchars($openSlots); ?></div>
                        <div class="stat-note">Beschikbare momenten vanaf vandaag</div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-label">Projecten online</div>
                        <div class="stat-number"><?php echo htmlspecialchars($projectsOnline); ?></div>
                        <div class="stat-note">Zichtbare portfolio-items</div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-label">Werkzaamheden online</div>
                        <div class="stat-number"><?php echo htmlspecialchars($servicesOnline); ?></div>
                        <div class="stat-note">Zichtbare diensten op de website</div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-label">TikTok-video's online</div>
                        <div class="stat-number"><?php echo htmlspecialchars($tiktokOnline); ?></div>
                        <div class="stat-note">Zichtbare TikTok-links</div>
                    </article>
                </section>

                <section class="dashboard-grid">
                    <article class="panel">
                        <div class="panel-header">
                            <h2>Laatste boekingen</h2>
                            <a class="small-link" href="boekingen.php">Alles bekijken</a>
                        </div>

                        <div class="booking-list">
                            <?php foreach ($latestBookings as $booking): ?>
                                <div class="booking-item">
                                    <div class="booking-top">
                                        <div class="booking-name">
                                            <?php echo htmlspecialchars($booking['customer_name']); ?>
                                        </div>

                                        <span class="badge <?php echo htmlspecialchars($booking['status']); ?>">
                                            <?php echo htmlspecialchars(statusLabel($booking['status'])); ?>
                                        </span>
                                    </div>

                                    <div class="booking-meta">
                                        <?php echo date('d-m-Y', strtotime($booking['booking_date'])); ?>
                                        om
                                        <?php echo htmlspecialchars(substr($booking['start_time'], 0, 5)); ?>
                                        -
                                        <?php echo htmlspecialchars(substr($booking['end_time'], 0, 5)); ?>
                                        <br>
                                        <?php echo htmlspecialchars($booking['service_type']); ?>
                                        ·
                                        <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($latestBookings)): ?>
                                <div class="empty-state">
                                    Er zijn nog geen boekingen. Zodra iemand via de website een afspraak aanvraagt,
                                    verschijnt die hier.
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>

                    <article class="panel">
                        <div class="panel-header">
                            <h2>Komende afspraken</h2>
                            <a class="small-link" href="agenda.php">Agenda openen</a>
                        </div>

                        <div class="booking-list">
                            <?php foreach ($upcomingBookings as $booking): ?>
                                <div class="booking-item">
                                    <div class="booking-top">
                                        <div class="booking-name">
                                            <?php echo htmlspecialchars($booking['customer_name']); ?>
                                        </div>

                                        <span class="badge confirmed">
                                            Bevestigd
                                        </span>
                                    </div>

                                    <div class="booking-meta">
                                        <?php echo date('d-m-Y', strtotime($booking['booking_date'])); ?>
                                        om
                                        <?php echo htmlspecialchars(substr($booking['start_time'], 0, 5)); ?>
                                        -
                                        <?php echo htmlspecialchars(substr($booking['end_time'], 0, 5)); ?>
                                        <br>
                                        <?php echo htmlspecialchars($booking['appointment_type']); ?>
                                        ·
                                        <?php echo htmlspecialchars($booking['service_type']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($upcomingBookings)): ?>
                                <div class="empty-state">
                                    Er zijn nog geen bevestigde komende afspraken.
                                    Zet boekingen op “bevestigd” om ze hier zichtbaar te maken.
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                </section>
            </main>
        </div>

    </div>
</div>

</body>
</html>