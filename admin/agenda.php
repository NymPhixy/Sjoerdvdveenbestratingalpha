<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';
require_once '../app/includes/admin-menu.php';

requireLogin();

$message = '';

// Huidige maand bepalen
$currentMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
$currentYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

if ($currentMonth < 1) {
    $currentMonth = 12;
    $currentYear--;
}

if ($currentMonth > 12) {
    $currentMonth = 1;
    $currentYear++;
}

$firstDayOfMonth = new DateTime("$currentYear-$currentMonth-01");
$daysInMonth = (int) $firstDayOfMonth->format('t');
$startWeekDay = (int) $firstDayOfMonth->format('N');

$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;

if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;

if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$monthNames = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maart',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Augustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'December'
];

// Tijdslot toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slot'])) {
    $date = $_POST['date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $created_by = $_SESSION['user_id'];

    if ($date && $start_time && $end_time) {
        if ($start_time >= $end_time) {
            $message = 'De eindtijd moet later zijn dan de starttijd.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO availability_slots
                (date, start_time, end_time, is_available, created_by)
                VALUES
                (:date, :start_time, :end_time, 1, :created_by)
            ");

            $stmt->execute([
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'created_by' => $created_by
            ]);

            $message = 'Tijdslot is toegevoegd.';
        }
    } else {
        $message = 'Vul datum, starttijd en eindtijd in.';
    }
}

// Tijdslot open/dicht zetten
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];

    $stmt = $pdo->prepare("
        UPDATE availability_slots
        SET is_available = NOT is_available
        WHERE id = :id
    ");

    $stmt->execute(['id' => $id]);

    header("Location: agenda.php?month=$currentMonth&year=$currentYear");
    exit;
}

// Tijdslot verwijderen
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM availability_slots WHERE id = :id");
    $stmt->execute(['id' => $id]);

    header("Location: agenda.php?month=$currentMonth&year=$currentYear");
    exit;
}

// Tijdblokkade toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_block'])) {
    $date = $_POST['block_date'] ?? '';
    $start_time = $_POST['block_start_time'] ?? '';
    $end_time = $_POST['block_end_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $created_by = $_SESSION['user_id'];

    if ($date && $start_time && $end_time) {
        if ($start_time >= $end_time) {
            $message = 'De eindtijd van de blokkade moet later zijn dan de starttijd.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO blocked_slots
                (date, start_time, end_time, reason, created_by)
                VALUES
                (:date, :start_time, :end_time, :reason, :created_by)
            ");

            $stmt->execute([
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'reason' => $reason,
                'created_by' => $created_by
            ]);

            $message = 'Blokkade is toegevoegd.';
        }
    } else {
        $message = 'Vul datum, starttijd en eindtijd in voor de blokkade.';
    }
}

// Blokkade verwijderen
if (isset($_GET['delete_block'])) {
    $id = (int) $_GET['delete_block'];

    $stmt = $pdo->prepare("DELETE FROM blocked_slots WHERE id = :id");
    $stmt->execute(['id' => $id]);

    header("Location: agenda.php?month=$currentMonth&year=$currentYear");
    exit;
}

// Boekingstatus aanpassen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_status'])) {
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];

    if ($booking_id && in_array($status, $allowed)) {
        $stmt = $pdo->prepare("
            UPDATE bookings
            SET status = :status
            WHERE id = :id
        ");

        $stmt->execute([
            'status' => $status,
            'id' => $booking_id
        ]);

        $message = 'Boekingstatus is bijgewerkt.';
    }
}

$monthStart = "$currentYear-" . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . "-01";
$monthEnd = "$currentYear-" . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . "-" . str_pad($daysInMonth, 2, '0', STR_PAD_LEFT);

// Tijdsloten ophalen
$stmt = $pdo->prepare("
    SELECT *
    FROM availability_slots
    WHERE date BETWEEN :start_date AND :end_date
    ORDER BY date ASC, start_time ASC
");
$stmt->execute([
    'start_date' => $monthStart,
    'end_date' => $monthEnd
]);
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Blokkades ophalen
$stmt = $pdo->prepare("
    SELECT *
    FROM blocked_slots
    WHERE date BETWEEN :start_date AND :end_date
    ORDER BY date ASC, start_time ASC
");
$stmt->execute([
    'start_date' => $monthStart,
    'end_date' => $monthEnd
]);
$blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Boekingen ophalen
$stmt = $pdo->prepare("
    SELECT *
    FROM bookings
    WHERE booking_date BETWEEN :start_date AND :end_date
    ORDER BY booking_date ASC, start_time ASC
");
$stmt->execute([
    'start_date' => $monthStart,
    'end_date' => $monthEnd
]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$calendarItems = [];

foreach ($slots as $slot) {
    $calendarItems[$slot['date']][] = [
        'type' => $slot['is_available'] ? 'slot-open' : 'slot-closed',
        'title' => $slot['is_available'] ? 'Open tijdslot' : 'Gesloten tijdslot',
        'time' => substr($slot['start_time'], 0, 5) . ' - ' . substr($slot['end_time'], 0, 5),
        'id' => $slot['id']
    ];
}

foreach ($blocks as $block) {
    $calendarItems[$block['date']][] = [
        'type' => 'blocked',
        'title' => $block['reason'] ?: 'Geblokkeerd',
        'time' => substr($block['start_time'], 0, 5) . ' - ' . substr($block['end_time'], 0, 5),
        'id' => $block['id']
    ];
}

foreach ($bookings as $booking) {
    $calendarItems[$booking['booking_date']][] = [
        'type' => 'booking-' . $booking['status'],
        'title' => $booking['customer_name'],
        'time' => substr($booking['start_time'], 0, 5) . ' - ' . substr($booking['end_time'], 0, 5),
        'id' => $booking['id'],
        'service' => $booking['service_type'],
        'status' => $booking['status']
    ];
}

function statusName($status)
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
    <title>Agenda Dashboard | Sjoerd van der Veen</title>
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
            --blue-dark: #101a3d;
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

        .calendar-shell {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px;
        }

        .dashboard-frame {
            background: var(--white);
            border-radius: 30px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
            min-height: 780px;
        }

        .calendar-topbar {
            min-height: 76px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 18px 28px;
            background: var(--white);
        }

        .brand {
            font-weight: 900;
            color: var(--green);
            font-size: 22px;
            letter-spacing: -0.04em;
            white-space: nowrap;
        }

        .searchbar {
            flex: 1;
            max-width: 520px;
            background: var(--beige);
            border-radius: 999px;
            padding: 12px 18px;
            color: var(--muted);
            font-size: 14px;
        }

        .top-actions {
            display: flex;
            gap: 12px;
            align-items: center;
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

        .nav-title,
        .filter-title {
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

        .filter-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 13px;
            color: var(--muted);
            font-size: 14px;
        }

        .filter-dot {
            width: 13px;
            height: 13px;
            border-radius: 4px;
            background: #d8dbe2;
        }

        .filter-dot.open {
            background: #2f7d55;
        }

        .filter-dot.pending {
            background: #c69b45;
        }

        .filter-dot.confirmed {
            background: #243f32;
        }

        .filter-dot.blocked {
            background: #9d2f2f;
        }

        .calendar-main {
            padding: 30px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 22px;
        }

        .calendar-title h1 {
            margin: 0;
            font-family: Georgia, serif;
            color: var(--dark);
            font-size: clamp(36px, 5vw, 58px);
            line-height: 1;
            letter-spacing: -0.06em;
        }

        .calendar-title p {
            margin: 8px 0 0;
            color: var(--muted);
            line-height: 1.7;
            max-width: 680px;
        }

        .month-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .month-actions a {
            border: 1px solid var(--border);
            background: var(--beige);
            border-radius: 999px;
            padding: 10px 14px;
            font-weight: 800;
            color: var(--green);
        }

        .message {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            background: var(--green-soft);
            color: var(--green);
            font-weight: 800;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            background: var(--white);
        }

        .weekday {
            padding: 14px;
            background: #f7f8f6;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            border-right: 1px solid var(--border);
            text-align: center;
        }

        .day-cell {
            min-height: 145px;
            padding: 12px;
            border-top: 1px solid var(--border);
            border-right: 1px solid var(--border);
            background: var(--white);
        }

        .day-cell.empty {
            background: #fafbfc;
        }

        .day-number {
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .today .day-number {
            display: inline-flex;
            background: var(--green);
            color: var(--white);
            width: 28px;
            height: 28px;
            border-radius: 999px;
            align-items: center;
            justify-content: center;
        }

        .calendar-event {
            display: block;
            border-radius: 10px;
            padding: 8px 9px;
            margin-bottom: 7px;
            font-size: 12px;
            line-height: 1.25;
            color: var(--white);
            box-shadow: 0 8px 18px rgba(18, 27, 44, 0.12);
        }

        .calendar-event strong {
            display: block;
            margin-bottom: 2px;
        }

        .slot-open {
            background: #2f7d55;
        }

        .slot-closed {
            background: #697080;
        }

        .blocked {
            background: #9d2f2f;
        }

        .booking-pending {
            background: #c69b45;
        }

        .booking-confirmed {
            background: #243f32;
        }

        .booking-cancelled {
            background: #7f8794;
        }

        .booking-completed {
            background: #2f5f8f;
        }

        .forms-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 28px;
        }

        .admin-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 14px 40px rgba(31, 42, 36, 0.06);
        }

        .admin-card h2 {
            margin: 0 0 10px;
            font-family: Georgia, serif;
            font-size: 30px;
            letter-spacing: -0.04em;
            color: var(--dark);
        }

        .admin-card p {
            color: var(--muted);
            line-height: 1.6;
        }

        .form-grid {
            display: grid;
            gap: 14px;
        }

        label {
            font-weight: 800;
            display: block;
            margin-bottom: 6px;
            color: var(--green);
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font: inherit;
            background: #fffdfa;
        }

        textarea {
            min-height: 88px;
            resize: vertical;
        }

        .list-section {
            margin-top: 28px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 18px;
            overflow: hidden;
            min-width: 780px;
        }

        .admin-table th,
        .admin-table td {
            padding: 13px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }

        .admin-table th {
            background: var(--green);
            color: var(--white);
        }

        .small-link {
            color: var(--green);
            font-weight: 800;
            margin-right: 8px;
        }

        .delete-link {
            color: var(--danger);
            font-weight: 800;
        }

        @media (max-width: 1050px) {
            .dashboard-body {
                grid-template-columns: 1fr;
            }

            .sidebar {
                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .forms-section {
                grid-template-columns: 1fr;
            }

            .calendar-header {
                flex-direction: column;
            }

            .calendar-grid {
                overflow-x: auto;
            }

            .day-cell {
                min-width: 140px;
            }

            .calendar-topbar {
                height: auto;
                flex-direction: column;
                align-items: flex-start;
            }

            .searchbar {
                width: 100%;
                max-width: none;
            }

            .top-actions {
                flex-wrap: wrap;
            }
        }
 </style>

<link rel="stylesheet" href="../public/assets/css/admin-responsive.css">
</head>
<body>

<div class="calendar-shell">
    <div class="dashboard-frame">

        <div class="calendar-topbar">
            <div class="brand">Sjoerd Dashboard</div>

            <div class="searchbar">
                Agenda, boekingen en beschikbaarheid
            </div>

            <div class="top-actions">
                <a class="button secondary" href="../public/index.php" target="_blank">Website bekijken</a>
                <a class="button primary" href="logout.php">Uitloggen</a>
            </div>
        </div>

        <div class="dashboard-body">
            <aside class="sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'] ?? 'G', 0, 1)); ?>
                    </div>

                    <strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'Gebruiker'); ?></strong>
                    <span>Rol: <?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?></span>
                </div>

                <div class="nav-title">Beheer</div>

                <?php renderAdminMenu('agenda'); ?>

                <div class="filter-title">Legenda</div>

                <div class="filter-item">
                    <span class="filter-dot open"></span>
                    Open tijdsloten
                </div>

                <div class="filter-item">
                    <span class="filter-dot pending"></span>
                    Boekingen in afwachting
                </div>

                <div class="filter-item">
                    <span class="filter-dot confirmed"></span>
                    Bevestigde boekingen
                </div>

                <div class="filter-item">
                    <span class="filter-dot blocked"></span>
                    Geblokkeerde tijden
                </div>
            </aside>

            <main class="calendar-main">
                <div class="calendar-header">
                    <div class="calendar-title">
                        <h1><?php echo $monthNames[$currentMonth] . ' ' . $currentYear; ?></h1>
                        <p>Bekijk en beheer Sjoerds tijdsloten, blokkades en klantafspraken.</p>
                    </div>

                    <div class="month-actions">
                        <a href="agenda.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>">Vorige</a>
                        <a href="agenda.php">Vandaag</a>
                        <a href="agenda.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>">Volgende</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="message">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="calendar-grid">
                    <div class="weekday">Ma</div>
                    <div class="weekday">Di</div>
                    <div class="weekday">Wo</div>
                    <div class="weekday">Do</div>
                    <div class="weekday">Vr</div>
                    <div class="weekday">Za</div>
                    <div class="weekday">Zo</div>

                    <?php for ($i = 1; $i < $startWeekDay; $i++): ?>
                        <div class="day-cell empty"></div>
                    <?php endfor; ?>

                    <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                        <?php
                            $dateString = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                            $isToday = $dateString === date('Y-m-d');
                            $items = $calendarItems[$dateString] ?? [];
                        ?>

                        <div class="day-cell <?php echo $isToday ? 'today' : ''; ?>">
                            <div class="day-number"><?php echo $day; ?></div>

                            <?php foreach ($items as $item): ?>
                                <div class="calendar-event <?php echo htmlspecialchars($item['type']); ?>">
                                    <strong><?php echo htmlspecialchars($item['time']); ?></strong>
                                    <?php echo htmlspecialchars($item['title']); ?>

                                    <?php if (!empty($item['service'])): ?>
                                        <br><?php echo htmlspecialchars($item['service']); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="forms-section">
                    <div class="admin-card">
                        <h2>Tijdslot toevoegen</h2>
                        <p>Maak beschikbare momenten aan waarop klanten een afspraak kunnen aanvragen.</p>

                        <form method="POST" class="form-grid">
                            <input type="hidden" name="add_slot" value="1">

                            <div>
                                <label>Datum</label>
                                <input type="date" name="date" required>
                            </div>

                            <div>
                                <label>Starttijd</label>
                                <input type="time" name="start_time" required>
                            </div>

                            <div>
                                <label>Eindtijd</label>
                                <input type="time" name="end_time" required>
                            </div>

                            <button class="button primary" type="submit">Tijdslot toevoegen</button>
                        </form>
                    </div>

                    <div class="admin-card">
                        <h2>Tijd blokkeren</h2>
                        <p>Blokkeer momenten waarop Sjoerd niet beschikbaar is.</p>

                        <form method="POST" class="form-grid">
                            <input type="hidden" name="add_block" value="1">

                            <div>
                                <label>Datum</label>
                                <input type="date" name="block_date" required>
                            </div>

                            <div>
                                <label>Starttijd</label>
                                <input type="time" name="block_start_time" required>
                            </div>

                            <div>
                                <label>Eindtijd</label>
                                <input type="time" name="block_end_time" required>
                            </div>

                            <div>
                                <label>Reden</label>
                                <textarea name="reason" placeholder="Bijvoorbeeld: privé-afspraak, werk op locatie, vrije dag"></textarea>
                            </div>

                            <button class="button primary" type="submit">Blokkade toevoegen</button>
                        </form>
                    </div>
                </div>

                <div class="list-section admin-card">
                    <h2>Boekingen deze maand</h2>

                    <div class="table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Tijd</th>
                                    <th>Klant</th>
                                    <th>Klus</th>
                                    <th>Status</th>
                                    <th>Wijzigen</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y', strtotime($booking['booking_date'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars(substr($booking['start_time'], 0, 5)); ?>
                                            -
                                            <?php echo htmlspecialchars(substr($booking['end_time'], 0, 5)); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong><br>
                                            <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['appointment_type']); ?><br>
                                            <?php echo htmlspecialchars($booking['service_type']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(statusName($booking['status'])); ?></td>
                                        <td>
                                            <form method="POST" class="form-grid">
                                                <input type="hidden" name="update_booking_status" value="1">
                                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>">

                                                <select name="status">
                                                    <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>In afwachting</option>
                                                    <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Bevestigd</option>
                                                    <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Geannuleerd</option>
                                                    <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Afgerond</option>
                                                </select>

                                                <button class="button primary" type="submit">Opslaan</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="6">Er zijn nog geen boekingen in deze maand.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="list-section admin-card">
                    <h2>Tijdsloten beheren</h2>

                    <div class="table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Tijd</th>
                                    <th>Status</th>
                                    <th>Acties</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($slots as $slot): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y', strtotime($slot['date'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars(substr($slot['start_time'], 0, 5)); ?>
                                            -
                                            <?php echo htmlspecialchars(substr($slot['end_time'], 0, 5)); ?>
                                        </td>
                                        <td><?php echo $slot['is_available'] ? 'Open' : 'Gesloten'; ?></td>
                                        <td>
                                            <a class="small-link" href="agenda.php?month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&toggle=<?php echo $slot['id']; ?>">Open/sluit</a>
                                            <a class="delete-link" href="agenda.php?month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&delete=<?php echo $slot['id']; ?>" onclick="return confirm('Weet je zeker dat je dit tijdslot wilt verwijderen?')">Verwijderen</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($slots)): ?>
                                    <tr>
                                        <td colspan="4">Geen tijdsloten deze maand.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="list-section admin-card">
                    <h2>Blokkades beheren</h2>

                    <div class="table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Tijd</th>
                                    <th>Reden</th>
                                    <th>Actie</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($blocks as $block): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y', strtotime($block['date'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars(substr($block['start_time'], 0, 5)); ?>
                                            -
                                            <?php echo htmlspecialchars(substr($block['end_time'], 0, 5)); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($block['reason']); ?></td>
                                        <td>
                                            <a class="delete-link" href="agenda.php?month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>&delete_block=<?php echo $block['id']; ?>" onclick="return confirm('Weet je zeker dat je deze blokkade wilt verwijderen?')">Verwijderen</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($blocks)): ?>
                                    <tr>
                                        <td colspan="4">Geen blokkades deze maand.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>

    </div>
</div>

</body>
</html>