<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';

requireLogin();

$message = '';

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

    $stmt->execute([
        'id' => $id
    ]);

    header('Location: agenda.php');
    exit;
}

// Tijdslot verwijderen
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM availability_slots WHERE id = :id");
    $stmt->execute([
        'id' => $id
    ]);

    header('Location: agenda.php');
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
    $stmt->execute([
        'id' => $id
    ]);

    header('Location: agenda.php');
    exit;
}

// Tijdsloten ophalen
$stmt = $pdo->prepare("
    SELECT availability_slots.*, users.name AS creator_name
    FROM availability_slots
    LEFT JOIN users ON availability_slots.created_by = users.id
    ORDER BY availability_slots.date ASC, availability_slots.start_time ASC
");
$stmt->execute();
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Blokkades ophalen
$stmt = $pdo->prepare("
    SELECT blocked_slots.*, users.name AS creator_name
    FROM blocked_slots
    LEFT JOIN users ON blocked_slots.created_by = users.id
    ORDER BY blocked_slots.date ASC, blocked_slots.start_time ASC
");
$stmt->execute();
$blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Agenda beheren</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">

    <style>
        .admin-page {
            max-width: 1150px;
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

        .admin-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .form-grid {
            display: grid;
            gap: 14px;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #d8d0c4;
            font: inherit;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
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

        .badge.closed {
            background: #f8dfdf;
            color: #9d2f2f;
        }

        .small-link {
            color: #243f32;
            font-weight: 700;
            margin-right: 10px;
        }

        .delete-link {
            color: #9d2f2f;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }

            .admin-top {
                align-items: flex-start;
                flex-direction: column;
            }
        }
  </style>

<link rel="stylesheet" href="../public/assets/css/admin-responsive.css">
</head>
<body>

<div class="admin-page">
    <div class="admin-top">
        <div>
            <h1>Agenda beheren</h1>
            <p>Ingelogd als: <?php echo htmlspecialchars($_SESSION['name']); ?> - <?php echo htmlspecialchars($_SESSION['role']); ?></p>
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

    <div class="admin-grid">
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
                    <textarea name="reason" placeholder="Bijvoorbeeld: priv�-afspraak, werk op locatie, vrije dag"></textarea>
                </div>

                <button class="button primary" type="submit">Blokkade toevoegen</button>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <h2>Beschikbare tijdsloten</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Tijd</th>
                    <th>Status</th>
                    <th>Aangemaakt door</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slots as $slot): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($slot['date']); ?></td>
                        <td>
                            <?php echo htmlspecialchars(substr($slot['start_time'], 0, 5)); ?>
                            -
                            <?php echo htmlspecialchars(substr($slot['end_time'], 0, 5)); ?>
                        </td>
                        <td>
                            <?php if ($slot['is_available']): ?>
                                <span class="badge">Open</span>
                            <?php else: ?>
                                <span class="badge closed">Gesloten</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($slot['creator_name'] ?? 'Onbekend'); ?></td>
                        <td>
                            <a class="small-link" href="agenda.php?toggle=<?php echo $slot['id']; ?>">Open/sluit</a>
                            <a class="delete-link" href="agenda.php?delete=<?php echo $slot['id']; ?>" onclick="return confirm('Weet je zeker dat je dit tijdslot wilt verwijderen?')">Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($slots)): ?>
                    <tr>
                        <td colspan="5">Nog geen tijdsloten toegevoegd.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-card">
        <h2>Geblokkeerde tijden</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Tijd</th>
                    <th>Reden</th>
                    <th>Aangemaakt door</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blocks as $block): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($block['date']); ?></td>
                        <td>
                            <?php echo htmlspecialchars(substr($block['start_time'], 0, 5)); ?>
                            -
                            <?php echo htmlspecialchars(substr($block['end_time'], 0, 5)); ?>
                        </td>
                        <td><?php echo htmlspecialchars($block['reason']); ?></td>
                        <td><?php echo htmlspecialchars($block['creator_name'] ?? 'Onbekend'); ?></td>
                        <td>
                            <a class="delete-link" href="agenda.php?delete_block=<?php echo $block['id']; ?>" onclick="return confirm('Weet je zeker dat je deze blokkade wilt verwijderen?')">Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($blocks)): ?>
                    <tr>
                        <td colspan="5">Nog geen blokkades toegevoegd.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
