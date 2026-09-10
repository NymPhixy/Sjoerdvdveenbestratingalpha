<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';
require_once '../app/functions/settings.php';
require_once '../app/includes/admin-menu.php';

requireLogin();
requireRole('admin');

$message = '';
$errors = [];

$allowedSettings = [
    'company_name',
    'phone',
    'whatsapp',
    'email',
    'location',
    'intro_text',
    'facebook_url',
    'instagram_url',
    'tiktok_url'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    foreach ($allowedSettings as $key) {
        $value = trim($_POST[$key] ?? '');

        if ($key === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Vul een geldig e-mailadres in.';
        }

        if (in_array($key, ['facebook_url', 'instagram_url', 'tiktok_url']) && $value && !filter_var($value, FILTER_VALIDATE_URL)) {
            $errors[] = 'Vul een geldige URL in bij social media.';
        }
    }

    if (empty($errors)) {
        foreach ($allowedSettings as $key) {
            $value = trim($_POST[$key] ?? '');
            saveSetting($pdo, $key, $value);
        }

        $message = 'Instellingen zijn opgeslagen.';
    }
}

$settings = getAllSettings($pdo);

function settingValue($settings, $key)
{
    return htmlspecialchars($settings[$key] ?? '');
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Instellingen | Sjoerd Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --dark: #101a16;
            --green: #243f32;
            --green-soft: #e9f3ea;
            --beige: #f7f4ef;
            --border: #e1dbd1;
            --muted: #6f746e;
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

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 14px 40px rgba(31, 42, 36, 0.06);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 800;
            color: var(--green);
        }

        input,
        textarea {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font: inherit;
            background: #fffdfa;
        }

        textarea {
            min-height: 140px;
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

        .help-text {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
            margin-top: 6px;
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
        }

        @media (max-width: 900px) {
            .dashboard-body {
                grid-template-columns: 1fr;
            }

            .sidebar {
                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .topbar-center {
                width: 100%;
                max-width: none;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
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
                Beheer contactgegevens, socials en algemene websiteteksten
            </div>

            <div class="topbar-actions">
                <a class="button secondary" href="../public/index.php" target="_blank">Website bekijken</a>
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

                <?php renderAdminMenu('instellingen'); ?>
            </aside>

            <main class="main">
                <section class="page-header">
                    <div>
                        <h1>Instellingen</h1>
                        <p>
                            Pas vaste websitegegevens aan zonder in de code te werken.
                            Deze gegevens kunnen later gebruikt worden op de homepage, contactknoppen en afspraakpagina.
                        </p>
                    </div>

                    <a class="button primary" href="../public/index.php" target="_blank">
                        Website bekijken
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

                <section class="card">
                    <form method="POST" class="form-grid">
                        <input type="hidden" name="save_settings" value="1">

                        <div class="form-full">
                            <label>Bedrijfsnaam</label>
                            <input type="text" name="company_name" value="<?php echo settingValue($settings, 'company_name'); ?>">
                        </div>

                        <div>
                            <label>Telefoonnummer</label>
                            <input type="text" name="phone" value="<?php echo settingValue($settings, 'phone'); ?>" placeholder="Bijvoorbeeld: 06 45570389">
                        </div>

                        <div>
                            <label>WhatsApp nummer</label>
                            <input type="text" name="whatsapp" value="<?php echo settingValue($settings, 'whatsapp'); ?>" placeholder="Bijvoorbeeld: 31645570389">
                            <div class="help-text">Gebruik landcode zonder +. Bijvoorbeeld: 31645570389.</div>
                        </div>

                        <div>
                            <label>E-mailadres</label>
                            <input type="email" name="email" value="<?php echo settingValue($settings, 'email'); ?>" placeholder="info@voorbeeld.nl">
                        </div>

                        <div>
                            <label>Werkgebied / locatie</label>
                            <input type="text" name="location" value="<?php echo settingValue($settings, 'location'); ?>" placeholder="Bijvoorbeeld: Assen en omgeving">
                        </div>

                        <div class="form-full">
                            <label>Intro tekst</label>
                            <textarea name="intro_text" placeholder="Korte introductie voor op de website"><?php echo settingValue($settings, 'intro_text'); ?></textarea>
                        </div>

                        <div>
                            <label>Facebook URL</label>
                            <input type="url" name="facebook_url" value="<?php echo settingValue($settings, 'facebook_url'); ?>" placeholder="https://facebook.com/...">
                        </div>

                        <div>
                            <label>Instagram URL</label>
                            <input type="url" name="instagram_url" value="<?php echo settingValue($settings, 'instagram_url'); ?>" placeholder="https://instagram.com/...">
                        </div>

                        <div class="form-full">
                            <label>TikTok profiel URL</label>
                            <input type="url" name="tiktok_url" value="<?php echo settingValue($settings, 'tiktok_url'); ?>" placeholder="https://www.tiktok.com/@...">
                        </div>

                        <div class="form-actions">
                            <button class="button primary" type="submit">
                                Instellingen opslaan
                            </button>
                        </div>
                    </form>
                </section>
            </main>
        </div>

    </div>
</div>

</body>
</html>