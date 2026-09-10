<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';

requireLogin();

$message = '';

// Werkzaamheid toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $title = trim($_POST['title'] ?? '');
    $main_category = $_POST['main_category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $created_by = $_SESSION['user_id'];

    if ($title && in_array($main_category, ['bestratingswerk', 'hovenierswerk'])) {
        $stmt = $pdo->prepare("
            INSERT INTO services (title, main_category, description, is_visible, created_by)
            VALUES (:title, :main_category, :description, 1, :created_by)
        ");

        $stmt->execute([
            'title' => $title,
            'main_category' => $main_category,
            'description' => $description,
            'created_by' => $created_by
        ]);

        $message = 'Werkzaamheid is toegevoegd.';
    } else {
        $message = 'Vul minimaal een titel en geldige categorie in.';
    }
}

// Zichtbaarheid wisselen
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];

    $stmt = $pdo->prepare("
        UPDATE services
        SET is_visible = NOT is_visible
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => $id
    ]);

    header('Location: werkzaamheden.php');
    exit;
}

// Werkzaamheden ophalen
$stmt = $pdo->prepare("
    SELECT services.*, users.name AS creator_name
    FROM services
    LEFT JOIN users ON services.created_by = users.id
    ORDER BY services.main_category, services.title
");
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Werkzaamheden beheren</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
    <style>
        .admin-page {
            max-width: 1100px;
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

        .form-grid {
            display: grid;
            gap: 14px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #d8d0c4;
            font: inherit;
        }

        textarea {
            min-height: 100px;
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

        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f7f4ef;
            color: #243f32;
            font-size: 13px;
            font-weight: 700;
        }

        .message {
            padding: 12px 14px;
            border-radius: 12px;
            background: #f7f4ef;
            margin-bottom: 20px;
        }

        .small-link {
            color: #243f32;
            font-weight: 700;
        }
 </style>

<link rel="stylesheet" href="../public/assets/css/admin-responsive.css">
</head>
<body>

<div class="admin-page">
    <div class="admin-top">
        <div>
            <h1>Werkzaamheden beheren</h1>
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

    <div class="admin-card">
        <h2>Nieuwe werkzaamheid toevoegen</h2>

        <form method="POST" class="form-grid">
            <input type="hidden" name="add_service" value="1">

            <div>
                <label>Titel</label>
                <input type="text" name="title" placeholder="Bijvoorbeeld: Grondwerk" required>
            </div>

            <div>
                <label>Hoofdcategorie</label>
                <select name="main_category" required>
                    <option value="">Kies categorie</option>
                    <option value="bestratingswerk">Bestratingswerk</option>
                    <option value="hovenierswerk">Hovenierswerk</option>
                </select>
            </div>

            <div>
                <label>Omschrijving</label>
                <textarea name="description" placeholder="Korte uitleg van deze werkzaamheid"></textarea>
            </div>

            <button class="button primary" type="submit">Werkzaamheid toevoegen</button>
        </form>
    </div>

    <div class="admin-card">
        <h2>Alle werkzaamheden</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Categorie</th>
                    <th>Omschrijving</th>
                    <th>Zichtbaar</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($service['title']); ?></td>
                        <td>
                            <span class="badge">
                                <?php echo htmlspecialchars($service['main_category']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($service['description']); ?></td>
                        <td>
                            <?php echo $service['is_visible'] ? 'Ja' : 'Nee'; ?>
                        </td>
                        <td>
                            <a class="small-link" href="werkzaamheden.php?toggle=<?php echo $service['id']; ?>">
                                Zichtbaarheid wisselen
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="5">Nog geen werkzaamheden gevonden.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
