<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Vul je e-mailadres en wachtwoord in.';
    } else {
        if (login($pdo, $email, $password)) {
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Ongeldige inloggegevens.';
    }
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Inloggen | Sjoerd Dashboard</title>
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
            --shadow: 0 24px 70px rgba(31, 42, 36, 0.14);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(36, 63, 50, 0.16), transparent 34%),
                linear-gradient(135deg, #f7f4ef 0%, #eef1ec 100%);
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .login-shell {
            width: 100%;
            max-width: 1050px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 32px;
            box-shadow: var(--shadow);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 0.9fr;
            min-height: 620px;
        }

        .login-brand {
            background:
                linear-gradient(rgba(16, 26, 22, 0.74), rgba(16, 26, 22, 0.74)),
                url('../public/assets/images/hero.jpg');
            background-size: cover;
            background-position: center;
            color: var(--white);
            padding: 46px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-name {
            font-weight: 900;
            font-size: 22px;
            letter-spacing: -0.04em;
        }

        .brand-content h1 {
            font-family: Georgia, serif;
            font-size: clamp(42px, 6vw, 70px);
            line-height: 0.95;
            letter-spacing: -0.07em;
            margin: 0 0 18px;
        }

        .brand-content p {
            color: rgba(255, 255, 255, 0.82);
            line-height: 1.7;
            max-width: 460px;
            margin: 0;
        }

        .login-card {
            padding: 46px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-card h2 {
            margin: 0 0 10px;
            font-family: Georgia, serif;
            font-size: 42px;
            letter-spacing: -0.05em;
            color: var(--green);
        }

        .login-card p {
            margin: 0 0 28px;
            color: var(--muted);
            line-height: 1.7;
        }

        .form-grid {
            display: grid;
            gap: 16px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            color: var(--green);
            font-weight: 900;
        }

        input {
            width: 100%;
            padding: 14px 15px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: #fffdfa;
            font: inherit;
            font-size: 16px;
        }

        input:focus {
            outline: 2px solid rgba(36, 63, 50, 0.18);
            border-color: var(--green);
        }

        .button {
            width: 100%;
            border: 0;
            border-radius: 999px;
            padding: 14px 18px;
            background: var(--green);
            color: var(--white);
            font-weight: 900;
            font-size: 15px;
            cursor: pointer;
            margin-top: 6px;
        }

        .button:hover {
            opacity: 0.94;
        }

        .error {
            padding: 14px 16px;
            border-radius: 14px;
            background: #f8dfdf;
            color: var(--danger);
            font-weight: 800;
            margin-bottom: 20px;
        }

        .login-help {
            margin-top: 22px;
            padding: 16px;
            border-radius: 16px;
            background: var(--beige);
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .login-help strong {
            color: var(--green);
        }

        .back-link {
            display: inline-flex;
            margin-top: 18px;
            color: var(--green);
            font-weight: 900;
        }

        @media (max-width: 900px) {
            body {
                align-items: flex-start;
                padding: 14px;
            }

            .login-shell {
                grid-template-columns: 1fr;
                border-radius: 24px;
                min-height: auto;
            }

            .login-brand {
                min-height: 320px;
                padding: 30px;
            }

            .login-card {
                padding: 30px;
            }
        }

        @media (max-width: 520px) {
            body {
                padding: 8px;
            }

            .login-shell {
                border-radius: 18px;
            }

            .login-brand {
                min-height: 260px;
                padding: 22px;
            }

            .brand-content h1 {
                font-size: 38px;
            }

            .brand-content p {
                font-size: 15px;
            }

            .login-card {
                padding: 22px;
            }

            .login-card h2 {
                font-size: 34px;
            }

            input {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<div class="login-shell">
    <section class="login-brand">
        <div class="brand-name">
            Sjoerd Dashboard
        </div>

        <div class="brand-content">
            <h1>Welkom terug.</h1>
            <p>
                Beheer projecten, werkzaamheden, TikTok-links, afspraken en aanvragen
                vanuit één overzichtelijke omgeving.
            </p>
        </div>
    </section>

    <section class="login-card">
        <h2>Inloggen</h2>
        <p>Log in om toegang te krijgen tot het beheerdersdashboard.</p>

        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-grid">
            <div>
                <label>E-mailadres</label>
                <input type="email" name="email" placeholder="admin@sjoerdwebsite.local" required>
            </div>

            <div>
                <label>Wachtwoord</label>
                <input type="password" name="password" placeholder="Vul je wachtwoord in" required>
            </div>

            <button class="button" type="submit">
                Inloggen
            </button>
        </form>

        <div class="login-help">
            <strong>Toegang:</strong><br>
            Alleen Ruben/admin en aangemaakte gebruikers kunnen hier inloggen.
        </div>

        <a class="back-link" href="../public/index.php">
            Terug naar website
        </a>
    </section>
</div>

</body>
</html>