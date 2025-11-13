<?php
// Force HTML content type
add_filter('wp_mail_content_type', function () {
    return 'text/html; charset=UTF-8';
});

// Force UTF-8 charset explicitly
add_filter('wp_mail_charset', function () {
    return 'UTF-8';
});

// Ensure PHPMailer sends HTML and use base64 to avoid =20 artifacts in some clients
add_action('phpmailer_init', function ($phpmailer) {
    $phpmailer->isHTML(true);
    $phpmailer->CharSet  = 'UTF-8';
    $phpmailer->Encoding = 'base64'; // alternative to quoted-printable
});
