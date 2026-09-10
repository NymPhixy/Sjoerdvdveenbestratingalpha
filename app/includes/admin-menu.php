<?php

function renderAdminMenu($activePage = '')
{
    $role = $_SESSION['role'] ?? '';

    $items = [
        [
            'label' => 'Overzicht',
            'href' => 'dashboard.php',
            'key' => 'dashboard'
        ],
        [
            'label' => 'Agenda',
            'href' => 'agenda.php',
            'key' => 'agenda'
        ],
        [
            'label' => 'Boekingen',
            'href' => 'boekingen.php',
            'key' => 'boekingen'
        ],
        [
            'label' => 'Werkzaamheden',
            'href' => 'werkzaamheden.php',
            'key' => 'werkzaamheden'
        ],
        [
            'label' => 'Projecten',
            'href' => 'projecten.php',
            'key' => 'projecten'
        ],
        [
            'label' => 'TikTok',
            'href' => 'tiktok.php',
            'key' => 'tiktok'
        ],
        [
            'label' => 'Instellingen',
            'href' => 'instellingen.php',
            'key' => 'instellingen'
        ],
    ];

    if ($role === 'admin') {
        $items[] = [
            'label' => 'Gebruikers',
            'href' => 'gebruikers.php',
            'key' => 'gebruikers'
        ];
    }

    echo '<nav class="admin-nav">';

    foreach ($items as $item) {
        $activeClass = $activePage === $item['key'] ? 'active' : '';

        echo '<a class="' . htmlspecialchars($activeClass) . '" href="' . htmlspecialchars($item['href']) . '">';
        echo htmlspecialchars($item['label']);
        echo '</a>';
    }

    echo '</nav>';
}