<?php

require_once '../app/config/database.php';
require_once '../app/functions/settings.php';

$settings = getAllSettings($pdo);

$companyName = $settings['company_name'] ?? 'Sjoerd van der Veen Bestrating & Hovenierswerk';
$phone = $settings['phone'] ?? '';
$whatsapp = $settings['whatsapp'] ?? '';
$email = $settings['email'] ?? '';
$location = $settings['location'] ?? '';

$whatsappUrl = $whatsapp ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) : '';
$phoneUrl = $phone ? 'tel:' . preg_replace('/[^0-9+]/', '', $phone) : '';
$mailUrl = $email ? 'mailto:' . $email : '';

$projectId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM projects
    WHERE id = :id
    AND is_visible = 1
    LIMIT 1
");

$stmt->execute([
    'id' => $projectId
]);

$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    http_response_code(404);
}

$stmt = $pdo->prepare("
    SELECT *
    FROM project_images
    WHERE project_id = :project_id
    ORDER BY id ASC
");

$stmt->execute([
    'project_id' => $projectId
]);

$projectImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>
        <?php if ($project): ?>
            <?php echo htmlspecialchars($project['title']); ?> | <?php echo htmlspecialchars($companyName); ?>
        <?php else: ?>
            Project niet gevonden | <?php echo htmlspecialchars($companyName); ?>
        <?php endif; ?>
    </title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .project-detail-page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 70px 24px;
        }

        .back-link {
            display: inline-flex;
            margin-bottom: 24px;
            color: var(--color-green);
            font-weight: 800;
        }

        .project-detail-hero {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
        }

        .project-detail-image,
        .project-detail-placeholder {
            width: 100%;
            height: 460px;
            object-fit: cover;
            display: block;
        }

        .project-detail-placeholder {
            background: #e9e2d8;
            color: var(--color-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .project-detail-content {
            padding: 34px;
        }

        .project-detail-content h1 {
            font-family: Georgia, serif;
            font-size: clamp(38px, 6vw, 72px);
            line-height: 1;
            letter-spacing: -0.06em;
            margin: 12px 0 18px;
            color: var(--color-dark);
        }

        .project-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .project-meta span {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            color: var(--color-green);
            font-weight: 800;
            font-size: 14px;
        }

        .project-detail-content p {
            color: var(--color-muted);
            line-height: 1.8;
            font-size: 17px;
            max-width: 820px;
        }

        .project-gallery {
            margin-top: 34px;
        }

        .project-gallery h2 {
            font-family: Georgia, serif;
            font-size: 38px;
            letter-spacing: -0.05em;
            margin: 0 0 20px;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .gallery-grid img {
            width: 100%;
            height: 260px;
            object-fit: cover;
            border-radius: 18px;
            border: 1px solid var(--color-border);
            box-shadow: var(--shadow-soft);
        }

        .project-cta {
            margin-top: 34px;
            background: var(--color-green);
            color: white;
            border-radius: var(--radius-lg);
            padding: 34px;
            box-shadow: var(--shadow-soft);
        }

        .project-cta h2 {
            font-family: Georgia, serif;
            font-size: 40px;
            letter-spacing: -0.05em;
            margin: 0 0 12px;
        }

        .project-cta p {
            color: rgba(255,255,255,0.78);
            line-height: 1.7;
            max-width: 680px;
        }

        .project-cta-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .project-cta .button.primary {
            background: white;
            color: var(--color-green);
        }

        .project-cta .button.secondary {
            color: white;
            border-color: rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.10);
        }

        .not-found-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 38px;
            box-shadow: var(--shadow-soft);
        }

        .not-found-card h1 {
            font-family: Georgia, serif;
            font-size: 52px;
            letter-spacing: -0.05em;
            margin: 0 0 12px;
        }

        .not-found-card p {
            color: var(--color-muted);
            line-height: 1.7;
        }

        @media (max-width: 900px) {
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .project-detail-image,
            .project-detail-placeholder {
                height: 340px;
            }
        }

        @media (max-width: 650px) {
            .project-detail-page {
                padding: 42px 16px;
            }

            .project-detail-content,
            .project-cta {
                padding: 24px;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .gallery-grid img {
                height: 240px;
            }

            .project-cta-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .project-cta-actions .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<header class="site-header">
    <nav class="navbar">
        <div class="logo">
            <?php echo htmlspecialchars($companyName); ?>
        </div>

        <ul class="nav-links">
            <li><a href="index.php#werkzaamheden">Werkzaamheden</a></li>
            <li><a href="index.php#projecten">Projecten</a></li>
            <li><a href="index.php#tiktok">TikTok</a></li>
            <li><a href="afspraak.php">Afspraak</a></li>
            <li><a href="index.php#contact">Contact</a></li>
        </ul>

        <?php if ($whatsappUrl): ?>
            <a class="nav-button" href="<?php echo htmlspecialchars($whatsappUrl); ?>" target="_blank">
                WhatsApp
            </a>
        <?php else: ?>
            <a class="nav-button" href="afspraak.php">
                Afspraak maken
            </a>
        <?php endif; ?>
    </nav>
</header>

<main class="project-detail-page">

    <a class="back-link" href="index.php#projecten">
        ← Terug naar projecten
    </a>

    <?php if ($project): ?>
        <article class="project-detail-hero">
            <?php if (!empty($project['cover_image'])): ?>
                <img
                    class="project-detail-image"
                    src="<?php echo htmlspecialchars($project['cover_image']); ?>"
                    alt="<?php echo htmlspecialchars($project['title']); ?>"
                >
            <?php else: ?>
                <div class="project-detail-placeholder">
                    Geen afbeelding beschikbaar
                </div>
            <?php endif; ?>

            <div class="project-detail-content">
                <span class="eyebrow">
                    <?php echo htmlspecialchars($project['category']); ?>
                </span>

                <h1>
                    <?php echo htmlspecialchars($project['title']); ?>
                </h1>

                <div class="project-meta">
                    <?php if (!empty($project['location'])): ?>
                        <span><?php echo htmlspecialchars($project['location']); ?></span>
                    <?php endif; ?>

                    <?php if (!empty($project['project_date'])): ?>
                        <span><?php echo date('d-m-Y', strtotime($project['project_date'])); ?></span>
                    <?php endif; ?>
                </div>

                <p>
                    <?php echo nl2br(htmlspecialchars($project['description'] ?: 'Binnenkort meer informatie over dit project.')); ?>
                </p>
            </div>
        </article>

        <?php if (!empty($projectImages)): ?>
            <section class="project-gallery">
                <h2>Meer beelden van dit project</h2>

                <div class="gallery-grid">
                    <?php foreach ($projectImages as $image): ?>
                        <img
                            src="<?php echo htmlspecialchars($image['image_path']); ?>"
                            alt="<?php echo htmlspecialchars($image['alt_text'] ?: $project['title']); ?>"
                        >
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="project-cta">
            <h2>Ook zo’n buitenruimte laten maken?</h2>

            <p>
                Plan direct een oriëntatiegesprek of neem contact op om uw idee met Sjoerd te bespreken.
            </p>

            <div class="project-cta-actions">
                <a class="button primary" href="afspraak.php">
                    Afspraak plannen
                </a>

                <?php if ($whatsappUrl): ?>
                    <a class="button secondary" href="<?php echo htmlspecialchars($whatsappUrl); ?>" target="_blank">
                        WhatsApp Sjoerd
                    </a>
                <?php endif; ?>

                <?php if ($phoneUrl): ?>
                    <a class="button secondary" href="<?php echo htmlspecialchars($phoneUrl); ?>">
                        Bel <?php echo htmlspecialchars($phone); ?>
                    </a>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="not-found-card">
            <span class="eyebrow">Niet gevonden</span>

            <h1>Project niet gevonden</h1>

            <p>
                Dit project bestaat niet of staat niet zichtbaar op de website.
            </p>

            <a class="button primary" href="index.php#projecten">
                Terug naar projecten
            </a>
        </section>
    <?php endif; ?>

</main>

</body>
</html>