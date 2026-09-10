<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';
require_once '../app/includes/admin-menu.php';

requireLogin();

$message = '';
$errors = [];

// Werkzaamheid toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $title = trim($_POST['title'] ?? '');
    $main_category = trim($_POST['main_category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $created_by = $_SESSION['user_id'];

    if (!$title) {
        $errors[] = 'Vul een titel in.';
    }

    if (!in_array($main_category, ['bestratingswerk', 'hovenierswerk'])) {
        $errors[] = 'Kies een geldige categorie.';
    }

    if (!$description) {
        $errors[] = 'Vul een omschrijving in.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO services
            (title, main_category, description, is_visible, created_by)
            VALUES
            (:title, :main_category, :description, 1, :created_by)
        ");

        $stmt->execute([
            'title' => $title,
            'main_category' => $main_category,
            'description' => $description,
            'created_by' => $created_by
        ]);

        $message = 'Werkzaamheid is toegevoegd.';
    }
}

// Zichtbaarheid aan/uit zetten
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

// Werkzaamheid verwijderen
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $pdo->prepare("
        DELETE FROM services
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
    SELECT *
    FROM services
    ORDER BY main_category ASC, title ASC
");
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

function categoryLabel($category)
{
    if ($category === 'bestratingswerk') {
        return 'Bestratingswerk';
    }

    if ($category === 'hovenierswerk') {
        return 'Hovenierswerk';
    }

    return $category;
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Werkzaamheden | Sjoerd Dashboard</title>
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

        .layout {
            display: grid;
            grid-template-columns: 0.8fr 1.2fr;
            gap: 24px;
            align-items: start;
        }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 14px 40px rgba(31, 42, 36, 0.06);
        }

        .card h2 {
            margin: 0 0 16px;
            font-family: Georgia, serif;
            font-size: 30px;
            letter-spacing: -0.04em;
            color: var(--dark);
        }

        .form-grid {
            display: grid;
            gap: 14px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 800;
            color: var(--green);
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font: inherit;
            background: #fffdfa;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        .message {
            padding: 14px 16px;
            border-radius: 14px;
            background: var(--green-soft);
            color: var(--green);
            font-weight: 800;
            margin-bottom: 20px;
        }

        .error-box {
            padding: 14px 16px;
            border-radius: 14px;
            background: #f8dfdf;
            color: var(--danger);
            font-weight: 700;
            margin-bottom: 20px;
        }

        .service-list {
            display: grid;
            gap: 14px;
        }

        .service-item {
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
            background: #fffdfa;
        }

        .service-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 10px;
        }

        .service-title {
            font-weight: 900;
            color: var(--green);
            font-size: 18px;
        }

        .service-description {
            color: var(--muted);
            line-height: 1.6;
            margin: 10px 0;
        }

        .badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .badge.category {
            background: var(--beige);
            color: var(--green);
            border: 1px solid var(--border);
        }

        .badge.visible {
            background: var(--green-soft);
            color: var(--green);
        }

        .badge.hidden {
            background: #f8dfdf;
            color: var(--danger);
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .small-link {
            color: var(--green);
            font-weight: 900;
        }

        .delete-link {
            color: var(--danger);
            font-weight: 900;
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

            .layout,
            .page-header {
                grid-template-columns: 1fr;
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
                Beheer de werkzaamheden die op de website worden getoond
            </div>

            <div class="topbar-actions">
                <a class="button secondary" href="../public/index.php#werkzaamheden" target="_blank">Website bekijken</a>
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

                <?php renderAdminMenu('werkzaamheden'); ?>
            </aside>

            <main class="main">
                <section class="page-header">
                    <div>
                        <h1>Werkzaamheden beheren</h1>
                        <p>
                            Voeg diensten toe, zet ze tijdelijk uit of verwijder ze.
                            Alleen zichtbare werkzaamheden worden op de publieke website getoond.
                        </p>
                    </div>

                    <a class="button primary" href="../public/index.php#werkzaamheden" target="_blank">
                        Bekijk werkzaamheden
                    </a>
                </section>

                <?php if ($message): ?>
                    <div class="message">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="layout">
                    <section class="card">
                        <h2>Nieuwe werkzaamheid</h2>

                        <form method="POST" class="form-grid">
                            <input type="hidden" name="add_service" value="1">

                            <div>
                                <label>Titel</label>
                                <input type="text" name="title" placeholder="Bijvoorbeeld: Terras aanleggen" required>
                            </div>

                            <div>
                                <label>Categorie</label>
                                <select name="main_category" required>
                                    <option value="">Kies categorie</option>
                                    <option value="bestratingswerk">Bestratingswerk</option>
                                    <option value="hovenierswerk">Hovenierswerk</option>
                                </select>
                            </div>

                            <div>
                                <label>Omschrijving</label>
                                <textarea name="description" placeholder="Beschrijf kort wat Sjoerd aanbiedt." required></textarea>
                            </div>

                            <button class="button primary" type="submit">
                                Werkzaamheid toevoegen
                            </button>
                        </form>
                    </section>

                    <section class="card">
                        <h2>Bestaande werkzaamheden</h2>

                        <div class="service-list">
                            <?php foreach ($services as $service): ?>
                                <article class="service-item">
                                    <div class="service-top">
                                        <div>
                                            <div class="service-title">
                                                <?php echo htmlspecialchars($service['title']); ?>
                                            </div>
                                            <br>
                                            <span class="badge category">
                                                <?php echo htmlspecialchars(categoryLabel($service['main_category'])); ?>
                                            </span>
                                        </div>

                                        <?php if ($service['is_visible']): ?>
                                            <span class="badge visible">Zichtbaar</span>
                                        <?php else: ?>
                                            <span class="badge hidden">Verborgen</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="service-description">
                                        <?php echo htmlspecialchars($service['description']); ?>
                                    </div>

                                    <div class="actions">
                                        <a class="small-link" href="werkzaamheden.php?toggle=<?php echo $service['id']; ?>">
                                            <?php echo $service['is_visible'] ? 'Verbergen' : 'Tonen'; ?>
                                        </a>

                                        <a
                                            class="delete-link"
                                            href="werkzaamheden.php?delete=<?php echo $service['id']; ?>"
                                            onclick="return confirm('Weet je zeker dat je deze werkzaamheid wilt verwijderen?')"
                                        >
                                            Verwijderen
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>

                            <?php if (empty($services)): ?>
                                <div class="empty-state">
                                    Er zijn nog geen werkzaamheden toegevoegd.
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </main>
        </div>

    </div>
</div>

</body>
</html>
