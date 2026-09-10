# Sjoerd van der Veen Bestrating & Hovenierswerk — Website & Beheersysteem

## 1. Projectomschrijving

Dit project is een maatwerk website en beheersysteem voor **Sjoerd van der Veen Bestrating & Hovenierswerk**.

De website is bedoeld om het werk van Sjoerd professioneel zichtbaar te maken en bezoekers eenvoudig contact te laten opnemen of een afspraak te laten aanvragen.

Het systeem bestaat uit twee onderdelen:

1. Een publieke website voor bezoekers en potentiële klanten.
2. Een beveiligde adminomgeving waarin werkzaamheden, projecten, TikTok-links, afspraken en instellingen beheerd kunnen worden.

Het project is gebouwd als PHP/MySQL-applicatie binnen Laragon.

---

## 2. Doel van het project

Het doel van dit project is om een professionele en makkelijk beheerbare website te bouwen voor een kleine ondernemer in bestrating en hovenierswerk.

De website moet:

- Sjoerd zijn werkzaamheden duidelijk presenteren.
- Projecten en portfolio-items tonen.
- Bezoekers laten zien wat voor soort werk Sjoerd uitvoert.
- Klanten de mogelijkheid geven om een afspraak aan te vragen.
- Contact via WhatsApp en telefoon laagdrempelig maken.
- Via een dashboard beheerbaar zijn zonder direct in code te werken.

---

## 3. Doelgroep

De doelgroep bestaat uit particuliere en zakelijke klanten in Assen en omgeving die hulp zoeken bij:

- Bestrating
- Grondwerk
- Tuinaanleg
- Tuinonderhoud
- Schuttingen / houtwerk
- Overige buitenklussen

De website is vooral gericht op mensen die snel willen zien wat Sjoerd aanbiedt en eenvoudig contact willen opnemen.

---

## 4. Technische stack

Het project gebruikt de volgende technieken:

- PHP
- MySQL / MariaDB
- PDO voor databaseverbinding
- HTML
- CSS
- JavaScript
- Laragon als lokale ontwikkelomgeving
- HeidiSQL voor databasebeheer
- Git en GitHub voor versiebeheer

---

## 5. Projectstructuur

De projectmap is opgebouwd uit meerdere onderdelen:

```text
Sjoerdvdveenbestrating/
│
├── admin/
│   ├── dashboard.php
│   ├── login.php
│   ├── logout.php
│   ├── agenda.php
│   ├── boekingen.php
│   ├── werkzaamheden.php
│   ├── projecten.php
│   ├── tiktok.php
│   ├── instellingen.php
│   └── gebruikers.php
│
├── app/
│   ├── config/
│   │   └── database.php
│   │
│   ├── functions/
│   │   ├── auth.php
│   │   ├── mail.php
│   │   └── settings.php
│   │
│   └── includes/
│       └── admin-menu.php
│
├── database/
│   └── setup.sql
│
├── public/
│   ├── index.php
│   ├── afspraak.php
│   ├── project.php
│   │
│   ├── assets/
│   │   ├── css/
│   │   │   ├── style.css
│   │   │   └── admin-responsive.css
│   │   │
│   │   ├── js/
│   │   │   └── script.js
│   │   │
│   │   └── videos/
│   │       └── hero.mp4
│   │
│   └── uploads/
│       └── .gitkeep
│
├── .gitignore
├── composer.json
├── composer.lock
└── README.md

 README.md
6. Publieke website

De publieke website bestaat uit de volgende pagina’s:

6.1 Homepage

Bestand:

public/index.php

De homepage toont:

Hero-sectie met achtergrondvideo
Introductietekst
Werkgebied
Call-to-action knoppen
Werkzaamheden per categorie
Projecten
TikTok-links
Afspraakblok
Contactblok

De homepage haalt gegevens dynamisch uit de database, waaronder:

Bedrijfsnaam
Telefoonnummer
WhatsApp-nummer
Werkgebied
Introductietekst
Social media links
Werkzaamheden
Projecten
TikTok-video’s
6.2 Afspraakpagina

Bestand:

public/afspraak.php

Op deze pagina kunnen bezoekers een afspraak aanvragen.

De bezoeker kiest:

Type afspraak
Hoofdcategorie
Type klus
Datum
Tijdslot
Naam
Telefoonnummer
E-mailadres
Woonplaats
Adres van de klus
Voorkeur voor contact
Omschrijving van de klus

De afspraak wordt opgeslagen in de tabel bookings.

De afspraak krijgt standaard de status:

pending

Dat betekent dat Sjoerd de afspraak nog moet bevestigen.

De afspraakpagina controleert ook of een tijdslot nog beschikbaar is. Tijdsloten worden niet getoond wanneer ze:

Al geboekt zijn
Geblokkeerd zijn
Niet meer in de toekomst liggen
Niet zichtbaar/beschikbaar zijn
6.3 Projectdetailpagina

Bestand:

public/project.php

Deze pagina toont één project in detail.

De pagina gebruikt een URL met project-ID:

public/project.php?id=1

De pagina toont:

Coverafbeelding
Titel
Categorie
Locatie
Projectdatum
Omschrijving
Extra projectafbeeldingen
Call-to-action om afspraak te maken of contact op te nemen

Wanneer een project niet bestaat of niet zichtbaar is, toont de pagina een nette foutmelding.

7. Adminomgeving

De adminomgeving bevindt zich in:

admin/

Gebruikers moeten eerst inloggen voordat ze toegang krijgen tot het dashboard.

7.1 Login

Bestand:

admin/login.php

Hier kunnen gebruikers inloggen met hun e-mailadres en wachtwoord.

Er zijn twee rollen:

admin
moderator

De admin heeft toegang tot extra beheeronderdelen, zoals gebruikersbeheer.

7.2 Dashboard

Bestand:

admin/dashboard.php

Het dashboard geeft een overzicht van belangrijke gegevens, zoals:

Aantal afspraken
Aantal projecten
Aantal werkzaamheden
Aantal TikTok-video’s
Recente aanvragen
Snelle links naar beheerpagina’s
7.3 Werkzaamheden beheren

Bestand:

admin/werkzaamheden.php

Hier kunnen werkzaamheden worden toegevoegd, verborgen of verwijderd.

Een werkzaamheid bestaat uit:

Titel
Categorie
Omschrijving
Zichtbaarheid

De huidige categorieën zijn:

bestrating
grondwerk
tuinaanleg
tuinonderhoud
schuttingen
overig

Deze categorieën worden gebruikt op:

De homepage
De afspraakpagina
Het admin dashboard

Wanneer een werkzaamheid zichtbaar staat, verschijnt deze automatisch op de publieke website en in het afspraakformulier.

7.4 Projecten beheren

Bestand:

admin/projecten.php

Hier kunnen projecten worden toegevoegd, verborgen of verwijderd.

Een project bestaat uit:

Titel
Categorie
Omschrijving
Locatie
Projectdatum
Coverafbeelding
Zichtbaarheid

Zichtbare projecten worden getoond op de homepage.

Via public/project.php?id=... kan een bezoeker doorklikken naar de detailpagina van een project.

7.5 Agenda beheren

Bestand:

admin/agenda.php

In de agenda kunnen beschikbare tijdsloten en geblokkeerde tijden beheerd worden.

Een beschikbaar tijdslot bestaat uit:

Datum
Starttijd
Eindtijd
Beschikbaarheid

Een geblokkeerd tijdslot bestaat uit:

Datum
Starttijd
Eindtijd
Reden

De afspraakpagina gebruikt deze data om alleen vrije momenten te tonen.

7.6 Boekingen beheren

Bestand:

admin/boekingen.php

Hier worden afspraakaanvragen van klanten weergegeven.

Een boeking bevat:

Type afspraak
Categorie
Type klus
Datum
Tijd
Naam klant
Telefoonnummer
E-mailadres
Adres
Woonplaats
Contactvoorkeur
Omschrijving
Status

De status kan zijn:

pending
confirmed
cancelled
completed
7.7 TikTok beheren

Bestand:

admin/tiktok.php

Hier kunnen TikTok-links worden toegevoegd en beheerd.

Een TikTok-item bestaat uit:

Titel
Video URL
Omschrijving
Zichtbaarheid

Zichtbare TikTok-video’s verschijnen automatisch op de homepage.

7.8 Instellingen beheren

Bestand:

admin/instellingen.php

Hier kunnen algemene websitegegevens beheerd worden.

Instellingen zijn onder andere:

Bedrijfsnaam
Telefoonnummer
WhatsApp-nummer
E-mailadres
Werkgebied
Introductietekst
Facebook URL
Instagram URL
TikTok URL

Deze gegevens worden opgeslagen in de tabel:

site_settings

De publieke website gebruikt deze instellingen automatisch.

7.9 Gebruikers beheren

Bestand:

admin/gebruikers.php

Deze pagina is alleen bedoeld voor admins.

Hier kunnen gebruikers worden beheerd, zoals:

Admin
Moderator

De admin kan gebruikers toevoegen, activeren/deactiveren en wachtwoorden aanpassen.

8. Database

De database heet:

sjoerd_website

De database wordt opgebouwd met:

database/setup.sql

Let op: dit bestand bevat DROP TABLE IF EXISTS. Wanneer dit script volledig opnieuw wordt uitgevoerd, worden bestaande data gewist.

9. Databasetabellen
9.1 users

Bevat de gebruikers van het dashboard.

Belangrijke velden:

id
name
email
password_hash
role
is_active
created_at

Rollen:

admin
moderator
9.2 services

Bevat de werkzaamheden die Sjoerd aanbiedt.

Belangrijke velden:

id
title
main_category
description
image
is_visible
created_by
created_at
updated_at

De kolom main_category gebruikt momenteel een ENUM met de categorieën:

bestrating
grondwerk
tuinaanleg
tuinonderhoud
schuttingen
overig
9.3 projects

Bevat projecten / portfolio-items.

Belangrijke velden:

id
title
category
description
location
project_date
cover_image
is_visible
created_by
created_at
updated_at
9.4 project_images

Bevat extra afbeeldingen per project.

Belangrijke velden:

id
project_id
image_path
alt_text
created_at

Deze tabel is bedoeld voor de projectdetailpagina.

9.5 tiktok_videos

Bevat TikTok-links.

Belangrijke velden:

id
title
video_url
description
is_visible
created_by
created_at
updated_at
9.6 availability_slots

Bevat beschikbare tijdsloten.

Belangrijke velden:

id
date
start_time
end_time
is_available
created_by
created_at
9.7 blocked_slots

Bevat geblokkeerde tijden.

Belangrijke velden:

id
date
start_time
end_time
reason
created_by
created_at
9.8 bookings

Bevat afspraakaanvragen van klanten.

Belangrijke velden:

id
appointment_type
main_category
service_type
booking_date
start_time
end_time
customer_name
customer_phone
customer_email
customer_address
customer_city
contact_preference
description
status
created_at
updated_at
9.9 site_settings

Bevat algemene website-instellingen.

Belangrijke velden:

id
setting_key
setting_value
updated_at

Voorbeelden van instellingen:

company_name
phone
whatsapp
email
location
intro_text
facebook_url
instagram_url
tiktok_url
10. Gebruikers en rollen

Standaard gebruikers:

Admin:
admin@sjoerdwebsite.local
admin123

Moderator:
sjoerd@sjoerdwebsite.local
sjoerd123

De admin heeft meer rechten dan de moderator.

Admin kan onder andere:

Gebruikers beheren
Instellingen beheren
Werkzaamheden beheren
Projecten beheren
Boekingen bekijken
Agenda beheren

Moderator kan vooral operationele onderdelen beheren.

11. Authenticatie

Authenticatie wordt geregeld via:

app/functions/auth.php

Belangrijke functies:

isLoggedIn()
requireLogin()
requireRole($role)
login($pdo, $email, $password)
logout()

Wanneer een gebruiker niet is ingelogd, wordt deze doorgestuurd naar:

admin/login.php

Voor admin-only pagina’s wordt gebruikt:

requireRole('admin');
12. Instellingenfunctie

Instellingen worden verwerkt via:

app/functions/settings.php

Belangrijke functies:

getSetting($pdo, $key, $default = '')
getAllSettings($pdo)
saveSetting($pdo, $key, $value)

Deze functies maken het mogelijk om websitegegevens uit de database te halen en op te slaan.

13. Mailfunctie

Bestand:

app/functions/mail.php

De mailfunctie bestaat al, maar is tijdelijk geparkeerd.

De functie:

sendBookingNotification($bookingData)

wordt aangeroepen wanneer een boeking wordt aangemaakt.

Op dit moment wordt er nog geen echte e-mail verstuurd. De boeking wordt wel opgeslagen in het dashboard.

Later kan deze functie gekoppeld worden aan bijvoorbeeld:

PHPMailer
Gmail SMTP
App Password
Eigen domeinmail
14. Afspraaklogica

De afspraakpagina werkt met drie soorten data:

Beschikbare tijdsloten uit availability_slots
Bestaande boekingen uit bookings
Geblokkeerde tijden uit blocked_slots

De pagina controleert of een tijdslot overlapt met een bestaande boeking of blokkade.

De overlapfunctie:

function overlaps($startA, $endA, $startB, $endB)
{
    return ($startA < $endB && $endA > $startB);
}

Hiermee wordt voorkomen dat klanten dubbele of bezette tijden boeken.

15. Categorieën

De huidige categorieën zijn:

bestrating
grondwerk
tuinaanleg
tuinonderhoud
schuttingen
overig

Deze worden gebruikt voor werkzaamheden en boekingen.

Momenteel staan deze categorieën nog als ENUM in de database. Dat betekent dat de categorieën vastliggen in de database-structuur.

Een mogelijke toekomstige verbetering is om categorieën beheerbaar te maken via een aparte tabel:

categories

Dan kan Sjoerd of Ruben later categorieën beheren via het dashboard zonder SQL aan te passen.

Voor de alpha-versie is de ENUM-oplossing voldoende.

16. Media en uploads

Projectafbeeldingen worden opgeslagen in:

public/uploads/

De hero-video staat in:

public/assets/videos/hero.mp4

De video wordt gebruikt als achtergrondvideo op de homepage.

Aanbevolen richtlijnen voor de video:

Formaat: mp4
Lengte: 5 tot 12 seconden
Resolutie: maximaal ongeveer 1280px breed
Geluid: niet nodig
Bestandsgrootte: bij voorkeur 2 tot 5 MB

De video hoeft niet in de database opgeslagen te worden. Alleen wanneer de video later via het dashboard beheerbaar moet worden, kan de bestandslocatie in site_settings worden opgeslagen.

17. GitHub en versiebeheer

De GitHub repository is:

git@github.com:NymPhixy/Sjoerdvdveenbestratingalpha.git

De hoofdbranch is:

main

Basisworkflow:

git status
git add .
git commit -m "Beschrijving van wijziging"
git push

Voorbeelden:

git commit -m "Fix service categories in admin and booking form"
git commit -m "Add hero video and project detail page"
git commit -m "Improve homepage styling"
18. .gitignore

De .gitignore voorkomt dat onnodige of gevoelige bestanden worden gecommit.

Belangrijke uitgesloten bestanden/mappen:

.env
node_modules/
vendor/
public/uploads/*

De uploadmap blijft wel bestaan door:

public/uploads/.gitkeep

Let op: database/setup.sql moet wél in de repo blijven, omdat dit de database-opzet documenteert.

19. Lokale installatie
19.1 Projectmap

Het project staat lokaal in:

C:\laragon\www\Sjoerdvdveenbestrating
19.2 Website openen

Publieke website:

http://localhost/Sjoerdvdveenbestrating/public/index.php

Afspraakpagina:

http://localhost/Sjoerdvdveenbestrating/public/afspraak.php

Admin login:

http://localhost/Sjoerdvdveenbestrating/admin/login.php
20. Database installeren

Open HeidiSQL en voer uit:

database/setup.sql

Daarmee wordt de database sjoerd_website aangemaakt met de benodigde tabellen en basisdata.

Let op: dit script wist bestaande tabellen voordat ze opnieuw worden aangemaakt.

21. Belangrijke aandachtspunten
21.1 Setupbestand wist data

Het bestand database/setup.sql bevat:

DROP TABLE IF EXISTS ...

Daarom moet dit bestand voorzichtig gebruikt worden. Bij opnieuw uitvoeren worden bestaande boekingen, projecten en werkzaamheden verwijderd.

21.2 Oude categorieën niet meer gebruiken

De oude categorieën waren:

bestratingswerk
hovenierswerk

Deze zijn vervangen door:

bestrating
grondwerk
tuinaanleg
tuinonderhoud
schuttingen
overig

Actieve bestanden mogen geen oude categorieën meer gebruiken voor databasewaardes.

Wel mag de tekst “Bestrating & Hovenierswerk” blijven staan als bedrijfsnaam of gewone websitetekst.

21.3 Back-upbestanden

Er kunnen backupbestanden aanwezig zijn zoals:

admin/werkzaamheden-backup.php
public/afspraak-backup.php

Deze bestanden worden niet actief gebruikt door de website.

Ze kunnen oude categorieën bevatten. Dat is geen probleem zolang ze niet actief worden aangeroepen.

22. Testscenario’s
22.1 Inloggen
Ga naar admin/login.php
Login als admin
Controleer of het dashboard opent
22.2 Instellingen aanpassen
Ga naar admin/instellingen.php
Pas telefoonnummer of intro tekst aan
Sla op
Controleer of de wijziging zichtbaar is op public/index.php
22.3 Werkzaamheid toevoegen
Ga naar admin/werkzaamheden.php
Voeg een werkzaamheid toe
Kies een categorie
Sla op
Controleer of deze zichtbaar is op de homepage
Controleer of deze zichtbaar is in het afspraakformulier
22.4 Project toevoegen
Ga naar admin/projecten.php
Voeg een project toe
Upload of kies een coverafbeelding
Zet project zichtbaar
Controleer of het project op de homepage staat
Klik op het project
Controleer of project.php?id=... opent
22.5 Afspraak maken
Ga naar public/afspraak.php
Kies type afspraak
Kies categorie
Kies type klus
Kies datum
Kies tijdslot
Vul klantgegevens in
Verstuur de aanvraag
Controleer of de boeking zichtbaar is in het dashboard
22.6 Tijdslot blokkeren
Ga naar admin/agenda.php
Blokkeer een tijd
Ga naar public/afspraak.php
Controleer of het geblokkeerde tijdslot niet meer beschikbaar is
23. Huidige status

De huidige alpha-versie bevat:

Publieke homepage
Hero-video
Werkzaamhedenoverzicht
Projectenoverzicht
Projectdetailpagina
TikTok-overzicht
Contactsectie
Afspraakformulier
Admin login
Dashboard
Werkzaamhedenbeheer
Projectenbeheer
TikTok-beheer
Agenda/tijdslotenbeheer
Boekingenbeheer
Instellingenbeheer
Gebruikersbeheer
GitHub versiebeheer
24. Mogelijke vervolgstappen

Mogelijke verbeteringen voor volgende versies:

Projecten uitbreiden met meerdere foto’s per project
Projecten kunnen bewerken in plaats van alleen toevoegen/verwijderen
Werkzaamheden kunnen bewerken
Categorieën beheerbaar maken via dashboard
Hero-video uploadbaar maken via instellingen
Automatische e-mailmelding bij nieuwe boeking
Google Calendar of Outlook Calendar koppeling
Zoekmachineoptimalisatie verbeteren
Betere mobiele navigatie
Contactformulier toevoegen
Reviews/testimonials toevoegen
Cookie/privacy pagina toevoegen
Live hosting en domeinnaam koppelen
25. Conclusie

Dit project vormt een complete alpha-versie van een professionele website met dashboard voor Sjoerd van der Veen Bestrating & Hovenierswerk.

De website is niet alleen een statische presentatiepagina, maar een beheerbaar systeem waarin content, afspraken en contactgegevens aangepast kunnen worden via een adminomgeving.

De huidige versie is geschikt als werkende basis voor verdere uitbreiding, optimalisatie en uiteindelijke livegang.


Daarna opslaan en committen:

```powershell
git add README.md
git commit -m "Add project documentation"
git push