<?php

function sendBookingNotification($bookingData)
{
    // Tijdelijk geparkeerd.
    // Later koppelen we hier PHPMailer + Gmail SMTP aan wanneer Sjoerd zijn Gmail/App Password beschikbaar is.

    error_log('Nieuwe boeking ontvangen voor: ' . $bookingData['customer_name']);

    return false;
}