<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';

requireLogin();

$message = '';

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

    if (!empty($_FILES['cover_image']['name'])) {
        $upload_dir = '../public/assets/uploads/projects/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['cover_image']['name']);
        $target_path = $upload_dir . $file_name;
        $db_path = 'assets/uploads/projects/' . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

        if (in_array($_FILES['cover_image']['type'], $allowed_types)) {
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
                $cover_image = $db_path;
            } else {
                $message = 'Afbeelding uploaden is mislukt.';
            }
        } else {
            $message = 'Alleen JPG, PNG en WEBP bestanden zijn toegestaan.';
        }
    }

    if ($title && $category) {
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
    } else {
        $message = 'Vul minimaal een titel en categorie in.';
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
    <title>Projecten beheren</title>
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

        .project-thumb {
            width: 90px;
            height: 65px;
            object-fit: cover;
            border-radius: 10px;
            background: #f7f4ef;
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
            margin-right: 10px;
        }

        .delete-link {
            color: #9d2f2f;
            font-weight: 700;
        }
 </style>

<link rel="stylesheet" href="../public/assets/css/admin-responsive.css">
</head>
<body>

<div class="admin-page">
    <div class="admin-top">
        <div>
            <h1>Projecten beheren</h1>
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
        <h2>Nieuw project toevoegen</h2>

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

            <label>
                <input type="checkbox" name="is_visible" checked>
                Zichtbaar op website
            </label>

            <button class="button primary" type="submit">Project toevoegen</button>
        </form>
    </div>

    <div class="admin-card">
        <h2>Alle projecten</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Titel</th>
                    <th>Categorie</th>
                    <th>Locatie</th>
                    <th>Zichtbaar</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td>
                            <?php if (!empty($project['cover_image'])): ?>
                                <img class="project-thumb" src="../public/<?php echo htmlspecialchars($project['cover_image']); ?>" alt="">
                            <?php else: ?>
                                Geen foto
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($project['title']); ?></td>
                        <td><span class="badge"><?php echo htmlspecialchars($project['category']); ?></span></td>
                        <td><?php echo htmlspecialchars($project['location']); ?></td>
                        <td><?php echo $project['is_visible'] ? 'Ja' : 'Nee'; ?></td>
                        <td>
                            <a class="small-link" href="projecten.php?toggle=<?php echo $project['id']; ?>">Zichtbaarheid</a>
                            <a class="delete-link" href="projecten.php?delete=<?php echo $project['id']; ?>" onclick="return confirm('Weet je zeker dat je dit project wilt verwijderen?')">Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="6">Nog geen projecten gevonden.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
