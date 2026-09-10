CREATE DATABASE IF NOT EXISTS sjoerd_website
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE sjoerd_website;

-- --------------------------------------------------------
-- Oude tabellen verwijderen voor schone herstart
-- Let op: dit wist bestaande data in deze database
-- --------------------------------------------------------

DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS blocked_slots;
DROP TABLE IF EXISTS availability_slots;
DROP TABLE IF EXISTS tiktok_videos;
DROP TABLE IF EXISTS project_images;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;

-- --------------------------------------------------------
-- Tabel: users
-- admin = Ruben
-- moderator = Sjoerd
-- --------------------------------------------------------

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'moderator') NOT NULL DEFAULT 'moderator',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- Tabel: services
-- Werkzaamheden van Sjoerd
-- --------------------------------------------------------

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    main_category ENUM('bestratingswerk', 'hovenierswerk') NOT NULL,
    description TEXT,
    image VARCHAR(255),
    is_visible BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- Tabel: projects
-- Projecten / portfolio-items
-- --------------------------------------------------------

CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(150),
    project_date DATE NULL,
    cover_image VARCHAR(255),
    is_visible BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- Tabel: project_images
-- Extra foto's per project
-- --------------------------------------------------------

CREATE TABLE project_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Tabel: tiktok_videos
-- TikTok-links van Sjoerd
-- --------------------------------------------------------

CREATE TABLE tiktok_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    video_url TEXT NOT NULL,
    description TEXT,
    is_visible BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- Tabel: availability_slots
-- Tijdsloten die Sjoerd openzet
-- --------------------------------------------------------

CREATE TABLE availability_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- Tabel: blocked_slots
-- Tijden waarop Sjoerd niet beschikbaar is
-- --------------------------------------------------------

CREATE TABLE blocked_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    reason VARCHAR(255),
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- --------------------------------------------------------
-- Tabel: bookings
-- Boekingen / afspraakaanvragen van klanten
-- --------------------------------------------------------

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_type VARCHAR(100) NOT NULL,
    main_category ENUM('bestratingswerk', 'hovenierswerk') NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    customer_email VARCHAR(150),
    customer_address VARCHAR(255),
    customer_city VARCHAR(100),
    contact_preference ENUM('bellen', 'whatsapp', 'email') DEFAULT 'bellen',
    description TEXT,

    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- Basisgebruikers
-- Wachtwoorden:
-- Ruben: admin123
-- Sjoerd: sjoerd123
-- --------------------------------------------------------

INSERT INTO users (name, email, password_hash, role, is_active)
VALUES
(
    'Ruben Janssen',
    'admin@sjoerdwebsite.local',
    '$2y$10$6Q7Wtb1WqsiUgmjT83lqmeB6fIx1mJNBGXtIKiMK/muNwrgW02wsu',
    'admin',
    TRUE
),
(
    'Sjoerd van der Veen',
    'sjoerd@sjoerdwebsite.local',
    '$2y$10$1N31EeUXP5yNkGYnNS75fe8b.OxyPRMQLhrIalGdL7aKkcdhBoqDu',
    'moderator',
    TRUE
);

-- --------------------------------------------------------
-- Basiswerkzaamheden
-- --------------------------------------------------------

INSERT INTO services (title, main_category, description, is_visible, created_by)
VALUES
(
    'Grondwerk',
    'bestratingswerk',
    'Een sterke basis voor iedere buitenruimte. Denk aan uitgraven, egaliseren en voorbereiden van de ondergrond voor bestrating of tuinaanleg.',
    TRUE,
    1
),
(
    'Straattuin',
    'bestratingswerk',
    'Een combinatie van straatwerk en groen, waarbij bestrating en tuin slim op elkaar aansluiten.',
    TRUE,
    1
),
(
    'Bestrating',
    'bestratingswerk',
    'Strak en duurzaam straatwerk voor opritten, terrassen, paden en buitenruimtes.',
    TRUE,
    1
),
(
    'Tuinontwerp',
    'hovenierswerk',
    'Van idee naar praktisch tuinplan. Sjoerd denkt mee over indeling, uitstraling en gebruik van de tuin.',
    TRUE,
    1
),
(
    'Tuinonderhoud',
    'hovenierswerk',
    'Voor een verzorgde tuin door het jaar heen. Denk aan snoeien, onderhoud, opruimen en het netjes houden van de buitenruimte.',
    TRUE,
    1
),
(
    'Siertuin',
    'hovenierswerk',
    'Een nette en sfeervolle tuin met oog voor uitstraling, beplanting en afwerking.',
    TRUE,
    1
);

-- --------------------------------------------------------
-- Testtijdsloten
-- Deze zorgen dat afspraak.php meteen iets kan tonen
-- --------------------------------------------------------

INSERT INTO availability_slots (date, start_time, end_time, is_available, created_by)
VALUES
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '10:00:00', TRUE, 1),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', '14:00:00', TRUE, 1),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', '11:00:00', TRUE, 1);

-- --------------------------------------------------------
-- Controle
-- --------------------------------------------------------

SELECT DATABASE();

SHOW TABLES;

SELECT id, name, email, role, is_active
FROM users;

SELECT id, title, main_category, is_visible
FROM services;

SELECT id, date, start_time, end_time, is_available
FROM availability_slots
ORDER BY date ASC, start_time ASC;