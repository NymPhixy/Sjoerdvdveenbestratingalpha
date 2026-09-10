<?php

function getSetting($pdo, $key, $default = '')
{
    $stmt = $pdo->prepare("
        SELECT setting_value
        FROM site_settings
        WHERE setting_key = :setting_key
        LIMIT 1
    ");

    $stmt->execute([
        'setting_key' => $key
    ]);

    $setting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$setting) {
        return $default;
    }

    return $setting['setting_value'];
}

function getAllSettings($pdo)
{
    $stmt = $pdo->prepare("
        SELECT setting_key, setting_value
        FROM site_settings
        ORDER BY setting_key ASC
    ");

    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $settings = [];

    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

function saveSetting($pdo, $key, $value)
{
    $stmt = $pdo->prepare("
        INSERT INTO site_settings (setting_key, setting_value)
        VALUES (:setting_key, :setting_value)
        ON DUPLICATE KEY UPDATE setting_value = :setting_value_update
    ");

    $stmt->execute([
        'setting_key' => $key,
        'setting_value' => $value,
        'setting_value_update' => $value
    ]);
}