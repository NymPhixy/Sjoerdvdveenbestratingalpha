<?php

require_once '../app/config/database.php';

$message = '';
$errors = [];

// Beschikbare tijdsloten ophalen
$stmt = $pdo->prepare("
    SELECT *
    FROM availability_slots
    WHERE is_available = 1
    AND date >= CURDATE()
    ORDER BY date ASC, start_time ASC
");
$stmt->execute();
$allSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bezette boekingen ophalen
$stmt = $pdo->prepare("
    SELECT booking_date, start_time, end_time
    FROM bookings
    WHERE status IN ('pending', 'confirmed')
");
$stmt->execute();
$bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Geblokkeerde tijden ophalen
$stmt = $pdo->prepare("
    SELECT date, start_time, end_time
    FROM blocked_slots
    WHERE date >= CURDATE()
");
$stmt->execute();
$blockedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

function overlaps($startA, $endA, $startB, $endB)
{
    return ($startA < $endB && $endA > $startB);
}

function slotIsBooked($slot, $bookedSlots)
{
    foreach ($bookedSlots as $booking) {
        if ($booking['booking_date'] === $slot['date']) {
            if (overlaps($slot['start_time'], $slot['end_time'], $booking['start_time'], $booking['end_time'])) {
                return true;
            }
        }
    }

    return false;
}

function slotIsBlocked($slot, $blockedSlots)
{
    foreach ($blockedSlots as $block) {
        if ($block['date'] === $slot['date']) {
            if (overlaps($slot['start_time'], $slot['end_time'], $block['start_time'], $block['end_time'])) {
                return true;
            }
        }
    }

    return false;
}

// Alleen vrije slots tonen
$availableSlots = array_filter($allSlots, function ($slot) use ($bookedSlots, $blockedSlots) {
    return !slotIsBooked($slot, $bookedSlots) && !slotIsBlocked($slot, $blockedSlots);
});

// Boeking verwerken
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slot_id = (int) ($_POST['slot_id'] ?? 0);
    $appointment_type = trim($_POST['appointment_type'] ?? '');
    $main_category = trim($_POST['main_category'] ?? '');
    $service_type = trim($_POST['service_type'] ?? '');

    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    $customer_city = trim($_POST['customer_city'] ?? '');
    $contact_preference = trim($_POST['contact_preference'] ?? 'bellen');
    $description = trim($_POST['description'] ?? '');

    if (!$slot_id) {
        $errors[] = 'Kies een beschikbaar tijdslot.';
    }

    if (!$appointment_type) {
        $errors[] = 'Kies een type afspraak.';
    }

    if (!in_array($main_category, ['bestratingswerk', 'hovenierswerk'])) {
        $errors[] = 'Kies bestratingswerk of hovenierswerk.';
    }

    if (!$service_type) {
        $errors[] = 'Kies een type klus.';
    }

    if (!$customer_name) {
        $errors[] = 'Vul uw naam in.';
    }

    if (!$customer_phone) {
        $errors[] = 'Vul uw telefoonnummer in.';
    }

    if (!in_array($contact_preference, ['bellen', 'whatsapp', 'email'])) {
        $contact_preference = 'bellen';
    }

    // Gekozen tijdslot ophalen
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM availability_slots
            WHERE id = :id
            AND is_available = 1
            LIMIT 1
        ");
        $stmt->execute(['id' => $slot_id]);
        $selectedSlot = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$selectedSlot) {
            $errors[] = 'Dit tijdslot bestaat niet of is niet beschikbaar.';
        } else {
            if (slotIsBooked($selectedSlot, $bookedSlots) || slotIsBlocked($selectedSlot, $blockedSlots)) {
                $errors[] = 'Dit tijdslot is helaas net niet meer beschikbaar.';
            }
        }
    }

    // Boeking opslaan
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO bookings
            (
                appointment_type,
                main_category,
                service_type,
                booking_date,
                start_time,
                end_time,
                customer_name,
                customer_phone,
                customer_email,
                customer_address,
                customer_city,
                contact_preference,
                description,
                status
            )
            VALUES
            (
                :appointment_type,
                :main_category,
                :service_type,
                :booking_date,
                :start_time,
                :end_time,
                :customer_name,
                :customer_phone,
                :customer_email,
                :customer_address,
                :customer_city,
                :contact_preference,
                :description,
                'pending'
            )
        ");

        $stmt->execute([
            'appointment_type' => $appointment_type,
            'main_category' => $main_category,
            'service_type' => $service_type,
            'booking_date' => $selectedSlot['date'],
            'start_time' => $selectedSlot['start_time'],
            'end_time' => $selectedSlot['end_time'],
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'customer_email' => $customer_email,
            'customer_address' => $customer_address,
            'customer_city' => $customer_city,
            'contact_preference' => $contact_preference,
            'description' => $description
        ]);

        $message = 'Bedankt voor uw aanvraag. Sjoerd neemt binnen 1–2 werkdagen contact met u op om de afspraak te bevestigen.';

        // Slots opnieuw ophalen na boeking
        $availableSlots = array_filter($availableSlots, function ($slot) use ($slot_id) {
            return (int) $slot['id'] !== $slot_id;
        });
    }
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Afspraak plannen | Sjoerd van der Veen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .booking-page {
            max-width: 1050px;
            margin: 0 auto;
            padding: 70px 24px;
        }

        .booking-layout {
            display: grid;
            grid-template-columns: 0.8fr 1.2fr;
            gap: 28px;
            align-items: start;
        }

        .booking-info,
        .booking-form {
            background: #ffffff;
            border: 1px solid #e1dbd1;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(31, 42, 36, 0.08);
        }

        .booking-info h1 {
            font-family: Georgia, serif;
            font-size: clamp(38px, 6vw, 64px);
            line-height: 1;
            letter-spacing: -0.05em;
            margin: 12px 0;
        }

        .booking-info p {
            color: #6f746e;
            line-height: 1.7;
        }

        .form-grid {
            display: grid;
            gap: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        label {
            font-weight: 700;
            display: block;
            margin-bottom: 6px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid #d8d0c4;
            font: inherit;
            background: #fff;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .slot-list {
            display: grid;
            gap: 10px;
        }

        .slot-option {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 12px 14px;
            border: 1px solid #e1dbd1;
            border-radius: 14px;
            background: #f7f4ef;
        }

        .message {
            padding: 14px 16px;
            border-radius: 14px;
            background: #e9f3ea;
            color: #243f32;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .error-box {
            padding: 14px 16px;
            border-radius: 14px;
            background: #f8dfdf;
            color: #9d2f2f;
            margin-bottom: 18px;
        }

        @media (max-width: 850px) {
            .booking-layout,
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<header class="site-header">
    <nav class="navbar">
        <div class="logo">Sjoerd van der Veen</div>

        <ul class="nav-links">
            <li><a href="index.php#werkzaamheden">Werkzaamheden</a></li>
            <li><a href="index.php#projecten">Projecten</a></li>
            <li><a href="index.php#tiktok">TikTok</a></li>
            <li><a href="afspraak.php">Afspraak</a></li>
            <li><a href="index.php#contact">Contact</a></li>
        </ul>

        <a class="nav-button" href="https://wa.me/31000000000" target="_blank">
            WhatsApp
        </a>
    </nav>
</header>

<main class="booking-page">
    <div class="booking-layout">
        <section class="booking-info">
            <span class="eyebrow">Afspraak plannen</span>
            <h1>Plan een oriëntatiegesprek</h1>

            <p>
                Kies een beschikbaar moment en vertel kort wat er aan uw tuin,
                straatwerk of buitenruimte moet gebeuren.
            </p>

            <p>
                Na het versturen ontvangt Sjoerd de aanvraag in zijn dashboard.
                Hij neemt binnen 1–2 werkdagen contact met u op om de afspraak te bevestigen.
            </p>

            <p>
                Staat er geen passend tijdslot tussen? Stuur dan direct een WhatsApp-bericht.
            </p>

            <a class="button secondary" href="https://wa.me/31000000000" target="_blank">
                WhatsApp Sjoerd
            </a>
        </section>

        <section class="booking-form">
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

            <form method="POST" class="form-grid">
                <div>
                    <label>Type afspraak</label>
                    <select name="appointment_type" required>
                        <option value="">Kies type afspraak</option>
                        <option value="Oriëntatiegesprek">Oriëntatiegesprek</option>
                        <option value="Locatiebezoek">Locatiebezoek</option>
                        <option value="Offertegesprek">Offertegesprek</option>
                        <option value="Tuinbespreking">Tuinbespreking</option>
                        <option value="Bestratingsklus bespreken">Bestratingsklus bespreken</option>
                    </select>
                </div>

                <div class="form-row">
                    <div>
                        <label>Hoofdcategorie</label>
                        <select name="main_category" id="main_category" required>
                            <option value="">Kies categorie</option>
                            <option value="bestratingswerk">Bestratingswerk</option>
                            <option value="hovenierswerk">Hovenierswerk</option>
                        </select>
                    </div>

                    <div>
                        <label>Type klus</label>
                        <select name="service_type" id="service_type" required>
                            <option value="">Kies eerst een hoofdcategorie</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label>Kies een beschikbaar tijdslot</label>

                    <div class="slot-list">
                        <?php foreach ($availableSlots as $slot): ?>
                            <label class="slot-option">
                                <input type="radio" name="slot_id" value="<?php echo htmlspecialchars($slot['id']); ?>" required>
                                <span>
                                    <?php echo date('d-m-Y', strtotime($slot['date'])); ?>
                                    van
                                    <?php echo htmlspecialchars(substr($slot['start_time'], 0, 5)); ?>
                                    tot
                                    <?php echo htmlspecialchars(substr($slot['end_time'], 0, 5)); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>

                        <?php if (empty($availableSlots)): ?>
                            <p>Er zijn momenteel geen beschikbare tijdsloten. Neem contact op via WhatsApp.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>Naam</label>
                        <input type="text" name="customer_name" required>
                    </div>

                    <div>
                        <label>Telefoonnummer</label>
                        <input type="text" name="customer_phone" required>
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>E-mailadres</label>
                        <input type="email" name="customer_email">
                    </div>

                    <div>
                        <label>Woonplaats</label>
                        <input type="text" name="customer_city">
                    </div>
                </div>

                <div>
                    <label>Adres van de klus</label>
                    <input type="text" name="customer_address">
                </div>

                <div>
                    <label>Voorkeur contact</label>
                    <select name="contact_preference">
                        <option value="bellen">Bellen</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">E-mail</option>
                    </select>
                </div>

                <div>
                    <label>Omschrijving van de klus</label>
                    <textarea name="description" placeholder="Beschrijf kort wat u wilt laten doen."></textarea>
                </div>

                <button class="button primary" type="submit">
                    Afspraak aanvragen
                </button>
            </form>
        </section>
    </div>
</main>

<script>
    const mainCategory = document.getElementById('main_category');
    const serviceType = document.getElementById('service_type');

    const options = {
        bestratingswerk: [
            'Grondwerk',
            'Straattuin',
            'Bestrating',
            'Oprit',
            'Terras',
            'Pad',
            'Anders'
        ],
        hovenierswerk: [
            'Tuinontwerp',
            'Tuinonderhoud',
            'Siertuin',
            'Tuinaanleg',
            'Beplanting',
            'Anders'
        ]
    };

    mainCategory.addEventListener('change', function () {
        const selected = this.value;

        serviceType.innerHTML = '<option value="">Kies type klus</option>';

        if (options[selected]) {
            options[selected].forEach(function (item) {
                const option = document.createElement('option');
                option.value = item;
                option.textContent = item;
                serviceType.appendChild(option);
            });
        }
    });
</script>

</body>
</html>
