<?php

require_once '../app/config/database.php';
require_once '../app/functions/mail.php';
require_once '../app/functions/settings.php';

$message = '';
$errors = [];

$settings = getAllSettings($pdo);

$companyName = $settings['company_name'] ?? 'Sjoerd van der Veen Bestrating & Hovenierswerk';
$phone = $settings['phone'] ?? '';
$whatsapp = $settings['whatsapp'] ?? '';
$email = $settings['email'] ?? '';
$location = $settings['location'] ?? '';
$introText = $settings['intro_text'] ?? 'Strak straatwerk en verzorgd hovenierswerk met oog voor detail.';

$whatsappUrl = $whatsapp ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsapp) : '';
$phoneUrl = $phone ? 'tel:' . preg_replace('/[^0-9+]/', '', $phone) : '';
$mailUrl = $email ? 'mailto:' . $email : '';

$validCategories = [
    'bestrating',
    'grondwerk',
    'tuinaanleg',
    'tuinonderhoud',
    'schuttingen',
    'overig'
];

$categoryLabels = [
    'bestrating' => 'Bestrating',
    'grondwerk' => 'Grondwerk',
    'tuinaanleg' => 'Tuinaanleg',
    'tuinonderhoud' => 'Tuinonderhoud',
    'schuttingen' => 'Schuttingen / houtwerk',
    'overig' => 'Overig / weet ik nog niet'
];

// Werkzaamheden ophalen voor het afspraakformulier
$stmt = $pdo->prepare("
    SELECT title, main_category
    FROM services
    WHERE is_visible = 1
    ORDER BY main_category ASC, title ASC
");
$stmt->execute();
$formServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$serviceOptions = [
    'bestrating' => [],
    'grondwerk' => [],
    'tuinaanleg' => [],
    'tuinonderhoud' => [],
    'schuttingen' => [],
    'overig' => []
];

foreach ($formServices as $service) {
    if (isset($serviceOptions[$service['main_category']])) {
        $serviceOptions[$service['main_category']][] = $service['title'];
    }
}

// Altijd een fallback-optie toevoegen per categorie
foreach ($serviceOptions as $categoryKey => $items) {
    if (!in_array('Anders', $items)) {
        $serviceOptions[$categoryKey][] = 'Anders';
    }
}

// Alle beschikbare tijdsloten ophalen
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
    WHERE status != 'cancelled'
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

// Alleen écht vrije slots overhouden
$availableSlots = array_values(array_filter($allSlots, function ($slot) use ($bookedSlots, $blockedSlots) {
    return !slotIsBooked($slot, $bookedSlots) && !slotIsBlocked($slot, $blockedSlots);
}));

// Slots groeperen per datum
$slotsByDate = [];

foreach ($availableSlots as $slot) {
    $slotsByDate[$slot['date']][] = $slot;
}

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

    if (!in_array($main_category, $validCategories)) {
        $errors[] = 'Kies een geldige categorie.';
    }

    if (!$service_type) {
        $errors[] = 'Kies een type klus.';
    }

    if ($service_type && isset($serviceOptions[$main_category])) {
        if (!in_array($service_type, $serviceOptions[$main_category])) {
            $errors[] = 'Kies een geldige klus uit de lijst.';
        }
    }

    if (!$customer_name) {
        $errors[] = 'Vul uw naam in.';
    }

    if (!$customer_phone) {
        $errors[] = 'Vul uw telefoonnummer in.';
    }

    if ($customer_email && !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vul een geldig e-mailadres in.';
    }

    if (!in_array($contact_preference, ['bellen', 'whatsapp', 'email'])) {
        $contact_preference = 'bellen';
    }

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

        $bookingData = [
            'appointment_type' => $appointment_type,
            'main_category' => $categoryLabels[$main_category] ?? $main_category,
            'service_type' => $service_type,
            'booking_date' => $selectedSlot['date'],
            'start_time' => substr($selectedSlot['start_time'], 0, 5),
            'end_time' => substr($selectedSlot['end_time'], 0, 5),
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'customer_email' => $customer_email ?: 'Niet ingevuld',
            'customer_address' => $customer_address ?: 'Niet ingevuld',
            'customer_city' => $customer_city ?: 'Niet ingevuld',
            'contact_preference' => $contact_preference,
            'description' => $description ?: 'Geen omschrijving ingevuld.'
        ];

        // Mailfunctie staat klaar, maar is tijdelijk geparkeerd.
        // De boeking wordt wel gewoon opgeslagen in het dashboard.
        sendBookingNotification($bookingData);

        $message = 'Bedankt voor uw aanvraag. Sjoerd neemt binnen 1–2 werkdagen contact met u op om de afspraak te bevestigen.';

        // Na boeken opnieuw vrije slots samenstellen
        $availableSlots = array_values(array_filter($availableSlots, function ($slot) use ($slot_id) {
            return (int) $slot['id'] !== $slot_id;
        }));

        $slotsByDate = [];

        foreach ($availableSlots as $slot) {
            $slotsByDate[$slot['date']][] = $slot;
        }
    }
}

$slotsJson = json_encode($slotsByDate);
$serviceOptionsJson = json_encode($serviceOptions, JSON_UNESCAPED_UNICODE);

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Afspraak plannen | <?php echo htmlspecialchars($companyName); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .booking-page {
            max-width: 1120px;
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

        .booking-contact-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .step-label {
            display: inline-flex;
            margin-bottom: 10px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f7f4ef;
            color: #b08a57;
            font-size: 13px;
            font-weight: 800;
        }

        .form-grid {
            display: grid;
            gap: 22px;
        }

        .form-section {
            border: 1px solid #eee7dc;
            border-radius: 18px;
            padding: 20px;
            background: #fffdfa;
        }

        .form-section h3 {
            margin: 0 0 14px;
            font-family: Georgia, serif;
            font-size: 26px;
            letter-spacing: -0.03em;
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

        .date-grid,
        .time-grid {
            display: grid;
            gap: 10px;
        }

        .date-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .time-grid {
            grid-template-columns: repeat(3, 1fr);
            margin-top: 14px;
        }

        .date-option,
        .time-option {
            cursor: pointer;
        }

        .date-option input,
        .time-option input {
            display: none;
        }

        .date-option span,
        .time-option span {
            display: block;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #e1dbd1;
            background: #f7f4ef;
            color: #243f32;
            font-weight: 800;
            text-align: center;
            transition: 0.2s ease;
        }

        .date-option input:checked + span,
        .time-option input:checked + span {
            background: #243f32;
            color: #ffffff;
            border-color: #243f32;
        }

        .time-option span {
            background: #ffffff;
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

        .error-box p {
            margin: 0 0 8px;
        }

        .error-box p:last-child {
            margin-bottom: 0;
        }

        .empty-slots {
            padding: 14px;
            border-radius: 14px;
            background: #f7f4ef;
            color: #6f746e;
            line-height: 1.6;
        }

        @media (max-width: 900px) {
            .booking-layout,
            .form-row {
                grid-template-columns: 1fr;
            }

            .date-grid,
            .time-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 650px) {
            .booking-page {
                padding: 42px 16px;
            }

            .booking-info,
            .booking-form {
                padding: 22px;
                border-radius: 20px;
            }

            .booking-contact-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .booking-contact-actions .button {
                width: 100%;
            }

            .form-section {
                padding: 16px;
            }

            .form-section h3 {
                font-size: 23px;
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
            <a class="nav-button" href="index.php#contact">
                Contact
            </a>
        <?php endif; ?>
    </nav>
</header>

<main class="booking-page">
    <div class="booking-layout">
        <section class="booking-info">
            <span class="eyebrow">
                <?php echo $location ? htmlspecialchars($location) : 'Afspraak plannen'; ?>
            </span>

            <h1>Plan een oriëntatiegesprek</h1>

            <p>
                Kies eerst een beschikbare datum en daarna een tijdslot.
                Vertel kort wat er aan uw tuin, straatwerk of buitenruimte moet gebeuren.
            </p>

            <p>
                Na het versturen ontvangt Sjoerd de aanvraag in zijn dashboard.
                Hij neemt binnen 1–2 werkdagen contact met u op om de afspraak te bevestigen.
            </p>

            <p>
                Staat er geen passend moment tussen? Neem dan direct contact op.
            </p>

            <div class="booking-contact-actions">
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

                <?php if ($mailUrl): ?>
                    <a class="button secondary" href="<?php echo htmlspecialchars($mailUrl); ?>">
                        Mail Sjoerd
                    </a>
                <?php endif; ?>
            </div>
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
                <div class="form-section">
                    <span class="step-label">Stap 1</span>
                    <h3>Waar gaat de afspraak over?</h3>

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

                    <br>

                    <div class="form-row">
                        <div>
                            <label>Hoofdcategorie</label>
                            <select name="main_category" id="main_category" required>
                                <option value="">Kies categorie</option>

                                <?php foreach ($categoryLabels as $categoryKey => $categoryLabel): ?>
                                    <option value="<?php echo htmlspecialchars($categoryKey); ?>">
                                        <?php echo htmlspecialchars($categoryLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Type klus</label>
                            <select name="service_type" id="service_type" required>
                                <option value="">Kies eerst een hoofdcategorie</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <span class="step-label">Stap 2</span>
                    <h3>Kies een datum</h3>

                    <?php if (!empty($slotsByDate)): ?>
                        <div class="date-grid">
                            <?php foreach ($slotsByDate as $date => $slots): ?>
                                <label class="date-option">
                                    <input type="radio" name="selected_date" value="<?php echo htmlspecialchars($date); ?>">
                                    <span>
                                        <?php echo date('d-m-Y', strtotime($date)); ?>
                                        <br>
                                        <?php echo count($slots); ?> tijdslot<?php echo count($slots) > 1 ? 'en' : ''; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-slots">
                            Er zijn momenteel geen beschikbare tijdsloten.
                            Neem contact op via WhatsApp of telefoon.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-section">
                    <span class="step-label">Stap 3</span>
                    <h3>Kies een tijd</h3>

                    <div id="timeSlots" class="time-grid">
                        <div class="empty-slots">
                            Kies eerst een datum om beschikbare tijden te zien.
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <span class="step-label">Stap 4</span>
                    <h3>Uw contactgegevens</h3>

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

                    <br>

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

                    <br>

                    <div>
                        <label>Adres van de klus</label>
                        <input type="text" name="customer_address">
                    </div>

                    <br>

                    <div>
                        <label>Voorkeur contact</label>
                        <select name="contact_preference">
                            <option value="bellen">Bellen</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="email">E-mail</option>
                        </select>
                    </div>

                    <br>

                    <div>
                        <label>Omschrijving van de klus</label>
                        <textarea name="description" placeholder="Beschrijf kort wat u wilt laten doen."></textarea>
                    </div>
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

    const options = <?php echo $serviceOptionsJson ?: '{}'; ?>;

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

    const slotsByDate = <?php echo $slotsJson ?: '{}'; ?>;
    const dateOptions = document.querySelectorAll('input[name="selected_date"]');
    const timeSlots = document.getElementById('timeSlots');

    dateOptions.forEach(function (dateOption) {
        dateOption.addEventListener('change', function () {
            const selectedDate = this.value;
            const slots = slotsByDate[selectedDate] || [];

            timeSlots.innerHTML = '';

            if (slots.length === 0) {
                timeSlots.innerHTML = '<div class="empty-slots">Geen beschikbare tijden op deze datum.</div>';
                return;
            }

            slots.forEach(function (slot) {
                const label = document.createElement('label');
                label.className = 'time-option';

                const input = document.createElement('input');
                input.type = 'radio';
                input.name = 'slot_id';
                input.value = slot.id;
                input.required = true;

                const span = document.createElement('span');

                const start = slot.start_time.substring(0, 5);
                const end = slot.end_time.substring(0, 5);

                span.textContent = start + ' - ' + end;

                label.appendChild(input);
                label.appendChild(span);

                timeSlots.appendChild(label);
            });
        });
    });
</script>

</body>
</html>