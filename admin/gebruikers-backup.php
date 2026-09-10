<?php

require_once '../app/config/database.php';
require_once '../app/functions/auth.php';

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

    if (!$password) {
        $errors[] = 'Vul een wachtwoord in.';
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

// Actief/inactief zetten
if (isset($_GET['toggle'])) {
    $userId = (int) $_GET['toggle'];

    // Voorkomen dat je jezelf per ongeluk uitschakelt
    if ($userId !== (int) $_SESSION['user_id']) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET is_active = NOT is_active
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $userId
        ]);
    }

    header('Location: gebruikers.php');
    exit;
}

// Gebruiker verwijderen
if (isset($_GET['delete'])) {
    $userId = (int) $_GET['delete'];

    // Voorkomen dat je jezelf verwijdert
    if ($userId !== (int) $_SESSION['user_id']) {
        $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $userId
        ]);
    }

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
    <title>Gebruikersbeheer | Sjoerd Dashboard</title>
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

        .page-shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        .topbar {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 22px 24px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
            margin-bottom: 24px;
        }

        .topbar h1 {
            margin: 0;
            font-family: Georgia, serif;
            font-size: 42px;
            letter-spacing: -0.05em;
            color: var(--green);
        }

        .topbar p {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .top-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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

        .table-wrapper {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 780px;
        }

        .admin-table th,
        .admin-table td {
            padding: 14px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: middle;
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
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .inline-form select {
            min-width: 130px;
        }

        .small-link {
            color: var(--green);
            font-weight: 900;
            margin-right: 10px;
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

        @media (max-width: 950px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<div class="page-shell">
    <header class="topbar">
        <div>
            <h1>Gebruikersbeheer</h1>
            <p>Alleen admins kunnen gebruikers toevoegen, rollen aanpassen en accounts beheren.</p>
        </div>

        <div class="top-actions">
            <a class="button secondary" href="dashboard.php">Terug naar dashboard</a>
            <a class="button primary" href="logout.php">Uitloggen</a>
        </div>
    </header>

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

                <button class="button primary" type="submit">Gebruiker toevoegen</button>
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
                            <th>Acties</th>
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

                                        <button class="button secondary" type="submit">Opslaan</button>
                                    </form>

                                    <br>

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
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5">Geen gebruikers gevonden.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

</body>
</html>
