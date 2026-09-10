<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';

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
    <title>Boekingen beheren</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">

    <style>
        .admin-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        .admin-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .admin-card {
            background: #ffffff;
            border: 1px solid #e1dbd1;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
        }

        .admin-table th,
        .admin-table td {
            padding: 14px;
            border-bottom: 1px solid #eee7dc;
            text-align: left;
            vertical-align: top;
        }

        .admin-table th {
            background: #243f32;
            color: #ffffff;
        }

        .message {
            padding: 12px 14px;
            border-radius: 12px;
            background: #f7f4ef;
            margin-bottom: 20px;
        }

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f7f4ef;
            color: #243f32;
            font-size: 13px;
            font-weight: 700;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.confirmed {
            background: #e9f3ea;
            color: #243f32;
        }

        .badge.cancelled {
            background: #f8dfdf;
            color: #9d2f2f;
        }

        .badge.completed {
            background: #e6edf5;
            color: #24476b;
        }

        select {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #d8d0c4;
            font: inherit;
        }

        .small-button {
            border: none;
            border-radius: 999px;
            padding: 10px 14px;
            background: #243f32;
            color: #ffffff;
            font-weight: 700;
            cursor: pointer;
        }

        .delete-link {
            color: #9d2f2f;
            font-weight: 700;
            display: inline-block;
            margin-top: 8px;
        }

        .booking-description {
            max-width: 260px;
            color: #6f746e;
            line-height: 1.5;
        }

        .contact-links a {
            display: block;
            color: #243f32;
            font-weight: 700;
            margin-bottom: 4px;
        }

        @media (max-width: 950px) {
            .admin-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .table-wrapper {
                overflow-x: auto;
            }

            .admin-table {
                min-width: 1000px;
            }
        }
   </style>

<link rel="stylesheet" href="../public/assets/css/admin-responsive.css">
</head>
<body>

<div class="admin-page">
    <div class="admin-top">
        <div>
            <h1>Boekingen beheren</h1>
            <p>
                Ingelogd als:
                <?php echo htmlspecialchars($_SESSION['name']); ?>
                -
                <?php echo htmlspecialchars($_SESSION['role']); ?>
            </p>
        </div>

        <div>
            <a class="button secondary" href="dashboard.php">Terug naar dashboard</a>
            <a class="button primary" href="logout.php">Uitloggen</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="admin-card">
        <h2>Alle afspraakaanvragen</h2>

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

                                    <br><br>

                                    <button class="small-button" type="submit">
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
                                Nog geen boekingen gevonden. Zodra een klant een afspraak aanvraagt,
                                verschijnt die hier.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
