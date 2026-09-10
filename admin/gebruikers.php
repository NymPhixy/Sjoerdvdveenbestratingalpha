<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';
require_once '../app/includes/admin-menu.php';

requireLogin();
requireRole('admin');

$message = '';
$errors = [];

// Gebruiker toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'moderator');

    if (!$name) {
        $errors[] = 'Vul een naam in.';
    }

    if (!$email) {
        $errors[] = 'Vul een e-mailadres in.';
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vul een geldig e-mailadres in.';
    }

    if (!$password) {
        $errors[] = 'Vul een wachtwoord in.';
    }

    if ($password && strlen($password) < 6) {
        $errors[] = 'Gebruik minimaal 6 tekens voor het wachtwoord.';
    }

    if (!in_array($role, ['admin', 'moderator'])) {
        $errors[] = 'Ongeldige rol gekozen.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute([
            'email' => $email
        ]);

        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $errors[] = 'Er bestaat al een gebruiker met dit e-mailadres.';
        }
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users
            (name, email, password_hash, role, is_active)
            VALUES
            (:name, :email, :password_hash, :role, 1)
        ");

        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role
        ]);

        $message = 'Gebruiker is toegevoegd.';
    }
}

// Rol aanpassen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $role = trim($_POST['role'] ?? '');

    if ($userId && in_array($role, ['admin', 'moderator'])) {
        if ($userId === (int) $_SESSION['user_id'] && $role !== 'admin') {
            $errors[] = 'Je kunt je eigen adminrol niet verwijderen.';
        } else {
            $stmt = $pdo->prepare("
                UPDATE users
                SET role = :role
                WHERE id = :id
            ");

            $stmt->execute([
                'role' => $role,
                'id' => $userId
            ]);

            $message = 'Rol is aangepast.';
        }
    }
}

// Wachtwoord aanpassen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $newPassword = trim($_POST['new_password'] ?? '');

    if (!$userId) {
        $errors[] = 'Gebruiker niet gevonden.';
    }

    if (!$newPassword) {
        $errors[] = 'Vul een nieuw wachtwoord in.';
    }

    if ($newPassword && strlen($newPassword) < 6) {
        $errors[] = 'Gebruik minimaal 6 tekens voor het nieuwe wachtwoord.';
    }

    if (empty($errors)) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users
            SET password_hash = :password_hash
            WHERE id = :id
        ");

        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $userId
        ]);

        $message = 'Wachtwoord is aangepast.';
    }
}

// Actief/inactief zetten
if (isset($_GET['toggle'])) {
    $userId = (int) $_GET['toggle'];

    if ($userId === (int) $_SESSION['user_id']) {
        header('Location: gebruikers.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET is_active = NOT is_active
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => $userId
    ]);

    header('Location: gebruikers.php');
    exit;
}

// Gebruiker verwijderen
if (isset($_GET['delete'])) {
    $userId = (int) $_GET['delete'];

    if ($userId === (int) $_SESSION['user_id']) {
        header('Location: gebruikers.php');
        exit;
    }

    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE id = :id
    ");

    $stmt->execute([
        'id' => $userId
    ]);

    header('Location: gebruikers.php');
    exit;
}

// Gebruikers ophalen
$stmt = $pdo->prepare("
    SELECT id, name, email, role, is_active, created_at
    FROM users
    ORDER BY created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function roleLabel($role)
{
    if ($role === 'admin') {
        return 'Admin';
    }

    if ($role === 'moderator') {
        return 'Moderator';
    }

    return $role;
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Gebruikers | Sjoerd Dashboard</title>
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

        .button.danger {
            background: var(--danger);
            color: var(--white);
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
            font-weight: 800;
            display: block;
            margin-bottom: 6px;
            color: var(--green);
        }

        input,
        select {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font: inherit;
            background: #fffdfa;
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
            margin-bottom: 20px;
            font-weight: 700;
        }

        .error-box p {
            margin: 0 0 8px;
        }

        .error-box p:last-child {
            margin-bottom: 0;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
        }

        .admin-table th,
        .admin-table td {
            padding: 14px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }

        .admin-table th {
            background: var(--green);
            color: var(--white);
        }

        .badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .badge.admin {
            background: var(--green);
            color: var(--white);
        }

        .badge.moderator {
            background: var(--beige);
            color: var(--green);
            border: 1px solid var(--border);
        }

        .badge.active {
            background: var(--green-soft);
            color: var(--green);
        }

        .badge.inactive {
            background: #f8dfdf;
            color: var(--danger);
        }

        .inline-form {
            display: grid;
            gap: 8px;
            margin-bottom: 10px;
        }

        .inline-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .small-link {
            color: var(--green);
            font-weight: 900;
        }

        .delete-link {
            color: var(--danger);
            font-weight: 900;
        }

        .self-note {
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .password-form {
            margin-top: 10px;
            display: grid;
            gap: 8px;
        }

        .empty-state {
            color: var(--muted);
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
                Beheer accounts, rollen en toegang tot het dashboard
            </div>

            <div class="topbar-actions">
                <a class="button secondary" href="dashboard.php">Dashboard</a>
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

                <?php renderAdminMenu('gebruikers'); ?>
            </aside>

            <main class="main">
                <section class="page-header">
                    <div>
                        <h1>Gebruikersbeheer</h1>
                        <p>
                            Voeg gebruikers toe, wijzig rollen en beheer wie toegang heeft tot het dashboard.
                            Deze pagina is alleen zichtbaar voor admins.
                        </p>
                    </div>

                    <a class="button primary" href="dashboard.php">Terug naar overzicht</a>
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
                        <h2>Gebruiker toevoegen</h2>

                        <form method="POST" class="form-grid">
                            <input type="hidden" name="add_user" value="1">

                            <div>
                                <label>Naam</label>
                                <input type="text" name="name" placeholder="Bijvoorbeeld: Sjoerd van der Veen" required>
                            </div>

                            <div>
                                <label>E-mailadres</label>
                                <input type="email" name="email" placeholder="naam@email.nl" required>
                            </div>

                            <div>
                                <label>Tijdelijk wachtwoord</label>
                                <input type="text" name="password" placeholder="Bijvoorbeeld: welkom123" required>
                            </div>

                            <div>
                                <label>Rol</label>
                                <select name="role" required>
                                    <option value="moderator">Moderator</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>

                            <button class="button primary" type="submit">
                                Gebruiker toevoegen
                            </button>
                        </form>
                    </section>

                    <section class="card">
                        <h2>Bestaande gebruikers</h2>

                        <div class="table-wrapper">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Naam</th>
                                        <th>E-mail</th>
                                        <th>Rol</th>
                                        <th>Status</th>
                                        <th>Beheer</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>

                                                <?php if ((int) $user['id'] === (int) $_SESSION['user_id']): ?>
                                                    <br>
                                                    <span class="self-note">Dit ben jij</span>
                                                <?php endif; ?>

                                                <br>
                                                <span class="self-note">
                                                    Sinds <?php echo date('d-m-Y', strtotime($user['created_at'])); ?>
                                                </span>
                                            </td>

                                            <td><?php echo htmlspecialchars($user['email']); ?></td>

                                            <td>
                                                <span class="badge <?php echo htmlspecialchars($user['role']); ?>">
                                                    <?php echo htmlspecialchars(roleLabel($user['role'])); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php if ($user['is_active']): ?>
                                                    <span class="badge active">Actief</span>
                                                <?php else: ?>
                                                    <span class="badge inactive">Inactief</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <form method="POST" class="inline-form">
                                                    <input type="hidden" name="update_role" value="1">
                                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">

                                                    <select name="role">
                                                        <option value="moderator" <?php echo $user['role'] === 'moderator' ? 'selected' : ''; ?>>
                                                            Moderator
                                                        </option>
                                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                                                            Admin
                                                        </option>
                                                    </select>

                                                    <button class="button secondary" type="submit">
                                                        Rol opslaan
                                                    </button>
                                                </form>

                                                <form method="POST" class="password-form">
                                                    <input type="hidden" name="update_password" value="1">
                                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">

                                                    <input type="text" name="new_password" placeholder="Nieuw wachtwoord">

                                                    <button class="button secondary" type="submit">
                                                        Wachtwoord wijzigen
                                                    </button>
                                                </form>

                                                <div class="inline-actions">
                                                    <?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?>
                                                        <a class="small-link" href="gebruikers.php?toggle=<?php echo $user['id']; ?>">
                                                            <?php echo $user['is_active'] ? 'Deactiveren' : 'Activeren'; ?>
                                                        </a>

                                                        <a
                                                            class="delete-link"
                                                            href="gebruikers.php?delete=<?php echo $user['id']; ?>"
                                                            onclick="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?')"
                                                        >
                                                            Verwijderen
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="self-note">Eigen account beschermd</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="5">
                                                <div class="empty-state">
                                                    Geen gebruikers gevonden.
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </main>
        </div>

    </div>
</div>

</body>
</html>