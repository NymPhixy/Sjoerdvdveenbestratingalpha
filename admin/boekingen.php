<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';
require_once '../app/includes/admin-menu.php';

requireLogin();

$message = '';

// Status aanpassen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];

    if ($booking_id && in_array($status, $allowed_statuses)) {
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
    } else {
        $message = 'Ongeldige boeking of status.';
    }
}

// Boeking verwijderen
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
    $stmt->execute([
        'id' => $id
    ]);

    header('Location: boekingen.php');
    exit;
}

// Boekingen ophalen
$stmt = $pdo->prepare("
    SELECT *
    FROM bookings
    ORDER BY booking_date ASC, start_time ASC, created_at DESC
");
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Boekingen | Sjoerd Dashboard</title>
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

        .page-header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: flex-start;
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-family: Georgia, serif;
            font-size: clamp(36px, 5vw, 58px);
            line-height: 1;
            letter-spacing: -0.06em;
            margin: 0 0 10px;
            color: var(--dark);
        }

        .page-header p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            max-width: 680px;
        }

        .message {
            padding: 14px 16px;
            border-radius: 14px;
            background: var(--green-soft);
            color: var(--green);
            font-weight: 800;
            margin-bottom: 20px;
        }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 14px 40px rgba(31, 42, 36, 0.06);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        .admin-table th,
        .admin-table td {
            padding: 14px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }

        .admin-table th {
            background: var(--green);
            color: var(--white);
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

        .contact-links a {
            display: block;
            color: var(--green);
            font-weight: 800;
            margin-bottom: 4px;
        }

        .booking-description {
            max-width: 260px;
            color: var(--muted);
            line-height: 1.5;
        }

        select {
            width: 100%;
            padding: 11px 12px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font: inherit;
            background: #fffdfa;
            margin-bottom: 10px;
        }

        .delete-link {
            color: var(--danger);
            font-weight: 900;
            display: inline-block;
            margin-top: 8px;
        }

        .empty-state {
            color: var(--muted);
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

            .page-header {
                flex-direction: column;
            }

            .topbar {
                height: auto;
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
                Beheer alle afspraakaanvragen van klanten
            </div>

            <div class="topbar-actions">
                <a class="button secondary" href="../public/afspraak.php" target="_blank">Boekingspagina testen</a>
                <a class="button primary" href="logout.php">Uitloggen</a>
            </div>
        </header>

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

                <?php renderAdminMenu('boekingen'); ?>
            </aside>

            <main class="main">
                <section class="page-header">
                    <div>
                        <h1>Boekingen beheren</h1>
                        <p>
                            Bekijk nieuwe afspraakaanvragen, klantgegevens en status. 
                            Zet aanvragen op bevestigd zodra Sjoerd de afspraak heeft goedgekeurd.
                        </p>
                    </div>

                    <a class="button primary" href="agenda.php">Agenda openen</a>
                </section>

                <?php if ($message): ?>
                    <div class="message">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <section class="card">
                    <div class="table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Tijd</th>
                                    <th>Status</th>
                                    <th>Klant</th>
                                    <th>Contact</th>
                                    <th>Klus</th>
                                    <th>Omschrijving</th>
                                    <th>Status wijzigen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('d-m-Y', strtotime($booking['booking_date'])); ?>
                                        </td>

                                        <td>
                                            <?php echo htmlspecialchars(substr($booking['start_time'], 0, 5)); ?>
                                            -
                                            <?php echo htmlspecialchars(substr($booking['end_time'], 0, 5)); ?>
                                        </td>

                                        <td>
                                            <span class="badge <?php echo htmlspecialchars($booking['status']); ?>">
                                                <?php echo htmlspecialchars(statusLabel($booking['status'])); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong><br>

                                            <?php if (!empty($booking['customer_city'])): ?>
                                                <?php echo htmlspecialchars($booking['customer_city']); ?><br>
                                            <?php endif; ?>

                                            <?php if (!empty($booking['customer_address'])): ?>
                                                <?php echo htmlspecialchars($booking['customer_address']); ?>
                                            <?php endif; ?>
                                        </td>

                                        <td class="contact-links">
                                            <a href="tel:<?php echo htmlspecialchars($booking['customer_phone']); ?>">
                                                <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                            </a>

                                            <?php if (!empty($booking['customer_email'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($booking['customer_email']); ?>">
                                                    <?php echo htmlspecialchars($booking['customer_email']); ?>
                                                </a>
                                            <?php endif; ?>

                                            <span>Voorkeur: <?php echo htmlspecialchars($booking['contact_preference']); ?></span>
                                        </td>

                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['appointment_type']); ?></strong><br>
                                            <?php echo htmlspecialchars($booking['main_category']); ?><br>
                                            <?php echo htmlspecialchars($booking['service_type']); ?>
                                        </td>

                                        <td>
                                            <div class="booking-description">
                                                <?php echo htmlspecialchars($booking['description'] ?: 'Geen omschrijving ingevuld.'); ?>
                                            </div>
                                        </td>

                                        <td>
                                            <form method="POST">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>">

                                                <select name="status">
                                                    <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>
                                                        In afwachting
                                                    </option>
                                                    <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>
                                                        Bevestigd
                                                    </option>
                                                    <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                                        Geannuleerd
                                                    </option>
                                                    <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>
                                                        Afgerond
                                                    </option>
                                                </select>

                                                <button class="button primary" type="submit">
                                                    Opslaan
                                                </button>
                                            </form>

                                            <a
                                                class="delete-link"
                                                href="boekingen.php?delete=<?php echo $booking['id']; ?>"
                                                onclick="return confirm('Weet je zeker dat je deze boeking wilt verwijderen?')"
                                            >
                                                Verwijderen
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                Nog geen boekingen gevonden. Zodra een klant een afspraak aanvraagt,
                                                verschijnt die hier.
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>

    </div>
</div>

</body>
</html>
