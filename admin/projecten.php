<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';
require_once '../app/includes/admin-menu.php';

requireLogin();

$message = '';
$errors = [];

// Project toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $project_date = $_POST['project_date'] ?? null;
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $created_by = $_SESSION['user_id'];

    $cover_image = null;

    if (!$title) {
        $errors[] = 'Vul een titel in.';
    }

    if (!$category) {
        $errors[] = 'Kies een categorie.';
    }

    if (!empty($_FILES['cover_image']['name'])) {
        $upload_dir = '../public/assets/uploads/projects/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $original_name = basename($_FILES['cover_image']['name']);
        $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $original_name);
        $file_name = time() . '_' . $safe_name;

        $target_path = $upload_dir . $file_name;
        $db_path = 'assets/uploads/projects/' . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

        if (in_array($_FILES['cover_image']['type'], $allowed_types)) {
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
                $cover_image = $db_path;
            } else {
                $errors[] = 'Afbeelding uploaden is mislukt.';
            }
        } else {
            $errors[] = 'Alleen JPG, PNG en WEBP bestanden zijn toegestaan.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO projects 
            (title, category, description, location, project_date, cover_image, is_visible, created_by)
            VALUES 
            (:title, :category, :description, :location, :project_date, :cover_image, :is_visible, :created_by)
        ");

        $stmt->execute([
            'title' => $title,
            'category' => $category,
            'description' => $description,
            'location' => $location,
            'project_date' => $project_date ?: null,
            'cover_image' => $cover_image,
            'is_visible' => $is_visible,
            'created_by' => $created_by
        ]);

        $message = 'Project is toegevoegd.';
    }
}

// Zichtbaarheid wisselen
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];

    $stmt = $pdo->prepare("
        UPDATE projects
        SET is_visible = NOT is_visible
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => $id
    ]);

    header('Location: projecten.php');
    exit;
}

// Project verwijderen
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $pdo->prepare("SELECT cover_image FROM projects WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($project && !empty($project['cover_image'])) {
        $file_path = '../public/' . $project['cover_image'];

        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = :id");
    $stmt->execute(['id' => $id]);

    header('Location: projecten.php');
    exit;
}

// Projecten ophalen
$stmt = $pdo->prepare("
    SELECT projects.*, users.name AS creator_name
    FROM projects
    LEFT JOIN users ON projects.created_by = users.id
    ORDER BY projects.created_at DESC
");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Projecten | Sjoerd Dashboard</title>
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

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--beige);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 13px 14px;
            cursor: pointer;
        }

        .checkbox-label input {
            width: auto;
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

        .project-list {
            display: grid;
            gap: 16px;
        }

        .project-item {
            border: 1px solid var(--border);
            border-radius: 20px;
            background: #fffdfa;
            overflow: hidden;
            display: grid;
            grid-template-columns: 150px 1fr;
        }

        .project-thumb {
            width: 150px;
            height: 100%;
            min-height: 150px;
            object-fit: cover;
            background: var(--beige);
        }

        .project-no-image {
            width: 150px;
            min-height: 150px;
            background: var(--beige);
            color: var(--muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            text-align: center;
            padding: 12px;
        }

        .project-content {
            padding: 18px;
        }

        .project-top {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .project-title {
            font-size: 20px;
            font-weight: 900;
            color: var(--green);
        }

        .project-meta {
            color: var(--muted);
            line-height: 1.6;
            font-size: 14px;
            margin-top: 8px;
        }

        .project-description {
            color: var(--muted);
            line-height: 1.6;
            margin-top: 10px;
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
            margin-top: 14px;
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

            .project-item {
                grid-template-columns: 1fr;
            }

            .project-thumb,
            .project-no-image {
                width: 100%;
                min-height: 220px;
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
                Beheer projecten, foto's en portfolio-items
            </div>

            <div class="topbar-actions">
                <a class="button secondary" href="../public/index.php#projecten" target="_blank">Website bekijken</a>
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

                <?php renderAdminMenu('projecten'); ?>
            </aside>

            <main class="main">
                <section class="page-header">
                    <div>
                        <h1>Projecten beheren</h1>
                        <p>
                            Voeg projecten toe met foto, categorie, locatie en omschrijving.
                            Alleen zichtbare projecten verschijnen op de publieke website.
                        </p>
                    </div>

                    <a class="button primary" href="../public/index.php#projecten" target="_blank">
                        Bekijk projecten
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
                        <h2>Nieuw project</h2>

                        <form method="POST" enctype="multipart/form-data" class="form-grid">
                            <input type="hidden" name="add_project" value="1">

                            <div>
                                <label>Titel</label>
                                <input type="text" name="title" placeholder="Bijvoorbeeld: Strakke oprit in Assen" required>
                            </div>

                            <div>
                                <label>Categorie</label>
                                <select name="category" required>
                                    <option value="">Kies categorie</option>
                                    <option value="Grondwerk">Grondwerk</option>
                                    <option value="Straattuin">Straattuin</option>
                                    <option value="Bestrating">Bestrating</option>
                                    <option value="Tuinontwerp">Tuinontwerp</option>
                                    <option value="Tuinonderhoud">Tuinonderhoud</option>
                                    <option value="Siertuin">Siertuin</option>
                                </select>
                            </div>

                            <div>
                                <label>Locatie</label>
                                <input type="text" name="location" placeholder="Bijvoorbeeld: Assen">
                            </div>

                            <div>
                                <label>Projectdatum</label>
                                <input type="date" name="project_date">
                            </div>

                            <div>
                                <label>Cover-afbeelding</label>
                                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/jpg">
                            </div>

                            <div>
                                <label>Omschrijving</label>
                                <textarea name="description" placeholder="Korte beschrijving van het project"></textarea>
                            </div>

                            <label class="checkbox-label">
                                <input type="checkbox" name="is_visible" checked>
                                Zichtbaar op website
                            </label>

                            <button class="button primary" type="submit">Project toevoegen</button>
                        </form>
                    </section>

                    <section class="card">
                        <h2>Alle projecten</h2>

                        <div class="project-list">
                            <?php foreach ($projects as $project): ?>
                                <article class="project-item">
                                    <?php if (!empty($project['cover_image'])): ?>
                                        <img
                                            class="project-thumb"
                                            src="../public/<?php echo htmlspecialchars($project['cover_image']); ?>"
                                            alt="<?php echo htmlspecialchars($project['title']); ?>"
                                        >
                                    <?php else: ?>
                                        <div class="project-no-image">
                                            Geen foto
                                        </div>
                                    <?php endif; ?>

                                    <div class="project-content">
                                        <div class="project-top">
                                            <div>
                                                <div class="project-title">
                                                    <?php echo htmlspecialchars($project['title']); ?>
                                                </div>

                                                <div class="project-meta">
                                                    <?php if (!empty($project['location'])): ?>
                                                        Locatie: <?php echo htmlspecialchars($project['location']); ?><br>
                                                    <?php endif; ?>

                                                    <?php if (!empty($project['project_date'])): ?>
                                                        Datum: <?php echo date('d-m-Y', strtotime($project['project_date'])); ?><br>
                                                    <?php endif; ?>

                                                    <?php if (!empty($project['creator_name'])): ?>
                                                        Toegevoegd door: <?php echo htmlspecialchars($project['creator_name']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div>
                                                <?php if ($project['is_visible']): ?>
                                                    <span class="badge visible">Zichtbaar</span>
                                                <?php else: ?>
                                                    <span class="badge hidden">Verborgen</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <span class="badge category">
                                            <?php echo htmlspecialchars($project['category']); ?>
                                        </span>

                                        <?php if (!empty($project['description'])): ?>
                                            <div class="project-description">
                                                <?php echo htmlspecialchars($project['description']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="actions">
                                            <a class="small-link" href="projecten.php?toggle=<?php echo $project['id']; ?>">
                                                <?php echo $project['is_visible'] ? 'Verbergen' : 'Tonen'; ?>
                                            </a>

                                            <a
                                                class="delete-link"
                                                href="projecten.php?delete=<?php echo $project['id']; ?>"
                                                onclick="return confirm('Weet je zeker dat je dit project wilt verwijderen?')"
                                            >
                                                Verwijderen
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>

                            <?php if (empty($projects)): ?>
                                <div class="empty-state">
                                    Nog geen projecten gevonden. Voeg het eerste project toe met een foto,
                                    categorie en korte omschrijving.
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