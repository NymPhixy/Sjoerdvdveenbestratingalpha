<?php

require_once '../app/config/database.php';
require_once '../app/functions/settings.php';

$settings = getAllSettings($pdo);

$companyName = $settings['company_name'] ?? 'Sjoerd van der Veen Bestrating & Hovenierswerk';
$phone = $settings['phone'] ?? '';
$whatsapp = $settings['whatsapp'] ?? '';
$email = $settings['email'] ?? '';
$location = $settings['location'] ?? '';
$introText = $settings['intro_text'] ?? 'Strak straatwerk en verzorgd hovenierswerk met oog voor detail.';
$facebookUrl = $settings['facebook_url'] ?? '';
$instagramUrl = $settings['instagram_url'] ?? '';
$tiktokUrl = $settings['tiktok_url'] ?? '';

$whatsappUrl = $whatsapp ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) : '';
$phoneUrl = $phone ? 'tel:' . preg_replace('/[^0-9+]/', '', $phone) : '';
$mailUrl = $email ? 'mailto:' . $email : '';

$categoryLabels = [
    'bestrating' => 'Bestrating',
    'grondwerk' => 'Grondwerk',
    'tuinaanleg' => 'Tuinaanleg',
    'tuinonderhoud' => 'Tuinonderhoud',
    'schuttingen' => 'Schuttingen / houtwerk',
    'overig' => 'Overig'
];

$categoryDescriptions = [
    'bestrating' => 'Opritten, terrassen, paden en sierbestrating netjes aangelegd.',
    'grondwerk' => 'Uitgraven, egaliseren en voorbereidende werkzaamheden.',
    'tuinaanleg' => 'Complete aanleg van tuinen, van indeling tot afwerking.',
    'tuinonderhoud' => 'Snoeien, opruimen en periodiek onderhoud van de tuin.',
    'schuttingen' => 'Schuttingen, afscheidingen en eenvoudig houtwerk in de tuin.',
    'overig' => 'Voor klussen die niet direct onder een vaste categorie vallen.'
];

// Werkzaamheden ophalen
$stmt = $pdo->prepare("
    SELECT *
    FROM services
    WHERE is_visible = 1
    ORDER BY main_category ASC, title ASC
");
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$servicesByCategory = [
    'bestrating' => [],
    'grondwerk' => [],
    'tuinaanleg' => [],
    'tuinonderhoud' => [],
    'schuttingen' => [],
    'overig' => []
];

foreach ($services as $service) {
    if (isset($servicesByCategory[$service['main_category']])) {
        $servicesByCategory[$service['main_category']][] = $service;
    }
}

// Projecten ophalen
$stmt = $pdo->prepare("
    SELECT *
    FROM projects
    WHERE is_visible = 1
    ORDER BY created_at DESC
    LIMIT 6
");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// TikTok-video's ophalen
$stmt = $pdo->prepare("
    SELECT *
    FROM tiktok_videos
    WHERE is_visible = 1
    ORDER BY created_at DESC
    LIMIT 3
");
$stmt->execute();
$tiktokVideos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($companyName); ?> | Bestrating & Hovenierswerk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
    <nav class="navbar">
        <div class="logo">
            <?php echo htmlspecialchars($companyName); ?>
        </div>

        <ul class="nav-links">
            <li><a href="#werkzaamheden">Werkzaamheden</a></li>
            <li><a href="#projecten">Projecten</a></li>
            <li><a href="#tiktok">TikTok</a></li>
            <li><a href="afspraak.php">Afspraak</a></li>
            <li><a href="#contact">Contact</a></li>
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

<main>

    <section class="hero hero-video-section">
    <video class="hero-bg-video" autoplay muted loop playsinline>
        <source src="assets/videos/hero.mp4" type="video/mp4">
    </video>

    <div class="hero-overlay"></div>

    <div class="hero-content">
        <span class="eyebrow">
            <?php echo $location ? htmlspecialchars($location) : 'Vakwerk buiten'; ?>
        </span>

        <h1>Luxe buitenruimtes, strak aangelegd</h1>

        <p>
            <?php echo htmlspecialchars($introText); ?>
        </p>

        <div class="hero-actions">
            <a href="afspraak.php" class="button primary">Plan een afspraak</a>
            <a href="#projecten" class="button secondary">Bekijk projecten</a>
        </div>
    </div>

    <div class="hero-card">
        <p>Modern straatwerk</p>
        <p>Hovenierswerk</p>
        <p>Oriëntatiegesprekken op afspraak</p>
    </div>
</section>

    <section id="werkzaamheden" class="section">
        <div class="section-heading">
            <span class="eyebrow">Werkzaamheden</span>

            <h2>Werkzaamheden voor tuin, straatwerk en buitenruimte</h2>

            <p>
                Kies direct de richting die past bij uw klus. Sjoerd helpt met een sterke basis,
                nette afwerking en een buitenruimte die praktisch én verzorgd is.
            </p>
        </div>

        <div class="category-grid">
            <?php foreach ($servicesByCategory as $categoryKey => $categoryServices): ?>
                <div class="category-block">
                    <h3><?php echo htmlspecialchars($categoryLabels[$categoryKey]); ?></h3>

                    <p>
                        <?php echo htmlspecialchars($categoryDescriptions[$categoryKey]); ?>
                    </p>

                    <div class="cards">
                        <?php foreach ($categoryServices as $service): ?>
                            <article class="service-card">
                                <span class="label">
                                    <?php echo htmlspecialchars($categoryLabels[$categoryKey]); ?>
                                </span>

                                <h4><?php echo htmlspecialchars($service['title']); ?></h4>

                                <p>
                                    <?php echo htmlspecialchars($service['description']); ?>
                                </p>
                            </article>
                        <?php endforeach; ?>

                        <?php if (empty($categoryServices)): ?>
                            <article class="service-card">
                                <h4>Nog geen werkzaamheden toegevoegd</h4>
                                <p>
                                    Werkzaamheden die zichtbaar staan in het dashboard verschijnen hier automatisch.
                                </p>
                            </article>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="projecten" class="section alt">
        <div class="section-heading">
            <span class="eyebrow">Projecten</span>

            <h2>Werk dat voor zichzelf spreekt</h2>

            <p>
                Bekijk een selectie van afgeronde projecten. Deze projecten kan Sjoerd zelf beheren
                vanuit het dashboard.
            </p>
        </div>

        <div class="project-grid">
            <?php foreach ($projects as $project): ?>
              <a class="project-card" href="project.php?id=<?php echo (int) $project['id']; ?>">
                    <?php if (!empty($project['cover_image'])): ?>
                        <img
                            src="<?php echo htmlspecialchars($project['cover_image']); ?>"
                            alt="<?php echo htmlspecialchars($project['title']); ?>"
                        >
                    <?php else: ?>
                        <div class="project-placeholder">
                            Geen afbeelding
                        </div>
                    <?php endif; ?>

                    <div class="project-content">
                        <span class="label">
                            <?php echo htmlspecialchars($project['category']); ?>
                        </span>

                        <h3><?php echo htmlspecialchars($project['title']); ?></h3>

                        <?php if (!empty($project['location'])): ?>
                            <p class="project-location">
                                <?php echo htmlspecialchars($project['location']); ?>
                            </p>
                        <?php endif; ?>

                        <p>
                            <?php echo htmlspecialchars($project['description'] ?: 'Binnenkort meer informatie over dit project.'); ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>

            <?php if (empty($projects)): ?>
                <div class="empty-state">
                    <h3>Nog geen projecten geplaatst</h3>
                    <p>Projecten die in het dashboard worden toegevoegd, verschijnen hier automatisch.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="tiktok" class="section">
        <div class="section-heading">
            <span class="eyebrow">TikTok</span>

            <h2>Bekijk Sjoerd aan het werk</h2>

            <p>
                Een selectie van video's waarin Sjoerd zijn werk, projecten en werkwijze laat zien.
                Deze video's kan hij zelf beheren vanuit het dashboard.
            </p>
        </div>

        <div class="tiktok-grid">
            <?php foreach ($tiktokVideos as $video): ?>
                <article class="tiktok-card">
                    <span class="label">TikTok</span>

                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>

                    <?php if (!empty($video['description'])): ?>
                        <p><?php echo htmlspecialchars($video['description']); ?></p>
                    <?php endif; ?>

                    <a
                        class="button secondary"
                        href="<?php echo htmlspecialchars($video['video_url']); ?>"
                        target="_blank"
                    >
                        Bekijk video
                    </a>
                </article>
            <?php endforeach; ?>

            <?php if (empty($tiktokVideos)): ?>
                <div class="empty-state">
                    <h3>Nog geen TikTok-video's geplaatst</h3>
                    <p>TikTok-links die in het dashboard worden toegevoegd, verschijnen hier automatisch.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($tiktokUrl): ?>
            <div class="section-action">
                <a class="button primary" href="<?php echo htmlspecialchars($tiktokUrl); ?>" target="_blank">
                    Bekijk TikTok-profiel
                </a>
            </div>
        <?php endif; ?>
    </section>

    <section id="afspraak" class="section">
        <div class="section-heading">
            <span class="eyebrow">Afspraak plannen</span>

            <h2>Plan een oriëntatiegesprek</h2>

            <p>
                Kies zelf een beschikbaar moment. Sjoerd ziet uw aanvraag terug in het dashboard
                en neemt contact op om de afspraak te bevestigen.
            </p>

            <a href="afspraak.php" class="button primary">
                Afspraak plannen
            </a>
        </div>
    </section>

    <section id="contact" class="section contact-section">
        <h2>Direct contact met Sjoerd</h2>

        <p>
            Liever direct overleggen? Bel of stuur een WhatsApp-bericht.
        </p>

        <div class="contact-actions">
            <?php if ($whatsappUrl): ?>
                <a class="button primary" href="<?php echo htmlspecialchars($whatsappUrl); ?>" target="_blank">
                    WhatsApp Sjoerd
                </a>
            <?php endif; ?>

            <?php if ($phoneUrl): ?>
                <a class="button secondary" href="<?php echo htmlspecialchars($phoneUrl); ?>">
                    Bel <?php echo htmlspecialchars($phone); ?>
                </a>
            <?php endif; ?>

            <?php if ($mailUrl): ?>
                <a class="button secondary" href="<?php echo htmlspecialchars($mailUrl); ?>">
                    Mail Sjoerd
                </a>
            <?php endif; ?>
        </div>

        <?php if ($location): ?>
            <p>
                Werkgebied: <?php echo htmlspecialchars($location); ?>
            </p>
        <?php endif; ?>

        <?php if ($facebookUrl || $instagramUrl || $tiktokUrl): ?>
            <div class="contact-actions">
                <?php if ($facebookUrl): ?>
                    <a class="button secondary" href="<?php echo htmlspecialchars($facebookUrl); ?>" target="_blank">
                        Facebook
                    </a>
                <?php endif; ?>

                <?php if ($instagramUrl): ?>
                    <a class="button secondary" href="<?php echo htmlspecialchars($instagramUrl); ?>" target="_blank">
                        Instagram
                    </a>
                <?php endif; ?>

                <?php if ($tiktokUrl): ?>
                    <a class="button secondary" href="<?php echo htmlspecialchars($tiktokUrl); ?>" target="_blank">
                        TikTok
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

</main>

<script src="assets/js/script.js"></script>
</body>
</html>