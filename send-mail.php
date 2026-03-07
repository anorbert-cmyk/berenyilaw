<?php
/**
 * Contact Form Handler - Berényi Law Office
 *
 * Biztonságos PHP mail script cPanel hosztinghoz.
 * AJAX JSON response + spam védelem.
 */

// Session indítás (output előtt kell!)
session_start();

// Konfiguráció
$recipient_email = "office@berenyi-law.hu";
$email_subject_prefix = "[Weboldal] ";

// JSON response
header('Content-Type: application/json; charset=UTF-8');

// Csak POST kéréseket fogadunk
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Érvénytelen kérés.']);
    exit;
}

// Honeypot spam védelem - ha ki van töltve, bot
if (!empty($_POST["_honey"])) {
    echo json_encode(['success' => true, 'message' => 'Üzenet elküldve!']);
    exit;
}

// Időalapú spam védelem - 3 mp-nél gyorsabb kitöltés = bot
if (isset($_POST["_timestamp"])) {
    $elapsed = time() - intval($_POST["_timestamp"]);
    if ($elapsed < 3) {
        echo json_encode(['success' => true, 'message' => 'Üzenet elküldve!']);
        exit;
    }
}

// Rate limiting - session alapon, 60 mp-enként max 1 üzenet
$rate_key = 'last_submit';
if (isset($_SESSION[$rate_key]) && (time() - $_SESSION[$rate_key]) < 60) {
    echo json_encode(['success' => false, 'message' => 'Kérjük várjon egy percet az újabb üzenet küldése előtt.']);
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

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(" ", $errors)]);
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

if ($mail_sent) {
    $_SESSION[$rate_key] = time();
    echo json_encode(['success' => true, 'message' => 'Üzenet sikeresen elküldve! Hamarosan felvesszük Önnel a kapcsolatot.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Hiba történt az üzenet küldésekor. Kérjük próbálja újra később.']);
}

exit;
