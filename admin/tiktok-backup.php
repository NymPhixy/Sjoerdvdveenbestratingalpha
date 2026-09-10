<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';

requireLogin();

$message = '';

// TikTok-video toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tiktok'])) {
    $title = trim($_POST['title'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $created_by = $_SESSION['user_id'];

    if ($title && $video_url) {
        $stmt = $pdo->prepare("
            INSERT INTO tiktok_videos 
            (title, video_url, description, is_visible, created_by)
            VALUES
            (:title, :video_url, :description, :is_visible, :created_by)
        ");

        $stmt->execute([
            'title' => $title,
            'video_url' => $video_url,
            'description' => $description,
            'is_visible' => $is_visible,
            'created_by' => $created_by
        ]);

        $message = 'TikTok-video is toegevoegd.';
    } else {
        $message = 'Vul minimaal een titel en TikTok-link in.';
    }
}

// Zichtbaarheid wisselen
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];

    $stmt = $pdo->prepare("
        UPDATE tiktok_videos
        SET is_visible = NOT is_visible
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => $id
    ]);

    header('Location: tiktok.php');
    exit;
}

// Verwijderen
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM tiktok_videos WHERE id = :id");
    $stmt->execute([
        'id' => $id
    ]);

    header('Location: tiktok.php');
    exit;
}

// TikTok-video's ophalen
$stmt = $pdo->prepare("
    SELECT tiktok_videos.*, users.name AS creator_name
    FROM tiktok_videos
    LEFT JOIN users ON tiktok_videos.created_by = users.id
    ORDER BY tiktok_videos.created_at DESC
");
$stmt->execute();
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>TikTok-video's beheren</title>
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

        .url-cell {
            max-width: 280px;
            word-break: break-all;
        }
    </style>

<link rel="stylesheet" href="../public/assets/css/admin-responsive.css">
</head>
<body>

<div class="admin-page">
    <div class="admin-top">
        <div>
            <h1>TikTok-video's beheren</h1>
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
        <h2>Nieuwe TikTok-video toevoegen</h2>

        <form method="POST" class="form-grid">
            <input type="hidden" name="add_tiktok" value="1">

            <div>
                <label>Titel</label>
                <input type="text" name="title" placeholder="Bijvoorbeeld: Nieuwe oprit aangelegd" required>
            </div>

            <div>
                <label>TikTok-link</label>
                <input type="url" name="video_url" placeholder="https://www.tiktok.com/@vdveenbestrating/video/..." required>
            </div>

            <div>
                <label>Omschrijving</label>
                <textarea name="description" placeholder="Korte uitleg van de video of klus"></textarea>
            </div>

            <label>
                <input type="checkbox" name="is_visible" checked>
                Zichtbaar op website
            </label>

            <button class="button primary" type="submit">TikTok-video toevoegen</button>
        </form>
    </div>

    <div class="admin-card">
        <h2>Alle TikTok-video's</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Link</th>
                    <th>Zichtbaar</th>
                    <th>Toegevoegd door</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($videos as $video): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($video['title']); ?></td>
                        <td class="url-cell">
                            <a href="<?php echo htmlspecialchars($video['video_url']); ?>" target="_blank">
                                <?php echo htmlspecialchars($video['video_url']); ?>
                            </a>
                        </td>
                        <td><?php echo $video['is_visible'] ? 'Ja' : 'Nee'; ?></td>
                        <td><?php echo htmlspecialchars($video['creator_name'] ?? 'Onbekend'); ?></td>
                        <td>
                            <a class="small-link" href="tiktok.php?toggle=<?php echo $video['id']; ?>">Zichtbaarheid</a>
                            <a class="delete-link" href="tiktok.php?delete=<?php echo $video['id']; ?>" onclick="return confirm('Weet je zeker dat je deze TikTok-video wilt verwijderen?')">Verwijderen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($videos)): ?>
                    <tr>
                        <td colspan="5">Nog geen TikTok-video's toegevoegd.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
