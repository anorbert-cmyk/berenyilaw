<?php
/**
 * Contact Form Handler - Berényi Law Office
 * 
 * Biztonságos PHP mail script cPanel hosztinghoz.
 * Nincs külső függőség, nincs regisztráció.
 */

// Konfiguráció
$recipient_email = "office@berenyi-law.hu";
$email_subject_prefix = "[Weboldal] ";
$redirect_success = "https://berenyi-law.hu/#contact?status=success";
$redirect_error = "https://berenyi-law.hu/#contact?status=error";

// CORS és biztonság
header("Content-Type: text/html; charset=UTF-8");

// Csak POST kéréseket fogadunk
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . $redirect_error);
    exit;
}

// Honeypot spam védelem - ha ki van töltve, bot
if (!empty($_POST["_honey"])) {
    header("Location: " . $redirect_success); // Fake success a botnak
    exit;
}

// Adatok kinyerése és tisztítása
$name = isset($_POST["name"]) ? trim(strip_tags($_POST["name"])) : "";
$email = isset($_POST["email"]) ? trim(strip_tags($_POST["email"])) : "";
$phone = isset($_POST["phone"]) ? trim(strip_tags($_POST["phone"])) : "";
$message = isset($_POST["message"]) ? trim(strip_tags($_POST["message"])) : "";

// Validáció
$errors = [];

if (empty($name)) {
    $errors[] = "A név megadása kötelező.";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Érvényes email cím megadása kötelező.";
}

if (empty($message)) {
    $errors[] = "Az üzenet megadása kötelező.";
}

// Ha van hiba, visszairányítás
if (!empty($errors)) {
    header("Location: " . $redirect_error . "&msg=" . urlencode(implode(" ", $errors)));
    exit;
}

// Email összeállítása
$subject = $email_subject_prefix . "Új megkeresés: " . $name;

$body = "
Új megkeresés érkezett a weboldalról.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

NÉV: $name

EMAIL: $email

TELEFON: $phone

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ÜZENET:

$message

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Küldve: " . date("Y-m-d H:i:s") . "
IP cím: " . $_SERVER["REMOTE_ADDR"] . "
";

// Email fejlécek
$headers = [
    "From: noreply@berenyi-law.hu",
    "Reply-To: $email",
    "X-Mailer: PHP/" . phpversion(),
    "Content-Type: text/plain; charset=UTF-8"
];

// Email küldése
$mail_sent = mail($recipient_email, $subject, $body, implode("\r\n", $headers));

// Eredmény alapján átirányítás
if ($mail_sent) {
    header("Location: " . $redirect_success);
} else {
    header("Location: " . $redirect_error . "&msg=" . urlencode("Hiba történt az üzenet küldésekor."));
}

exit;
?>
