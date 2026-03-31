<?php
/**
 * Contact Form Handler - Berényi Law Office
 *
 * Biztonságos PHP mail script cPanel hosztinghoz.
 * AJAX JSON response + spam védelem.
 */

// CORS headers — allow both bare and www domains
$allowed_origins = [
    'https://berenyi-law.hu',
    'https://www.berenyi-law.hu',
];
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');

// Handle CORS preflight
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

// Session indítás (output előtt kell!)
session_start();

// Konfiguráció
$recipient_email = "office@berenyi-law.hu";
$email_subject_prefix = "[Weboldal] ";

// JSON response
header('Content-Type: application/json; charset=UTF-8');

// CSRF token generálás GET kérésre (AJAX token lekérés)
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["csrf_token"])) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    echo json_encode(['csrf_token' => $_SESSION['csrf_token']]);
    exit;
}

// Csak POST kéréseket fogadunk
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Érvénytelen kérés.']);
    exit;
}

// CSRF token ellenőrzés
$submitted_token = isset($_POST["_csrf_token"]) ? $_POST["_csrf_token"] : "";
if (empty($submitted_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted_token)) {
    echo json_encode(['success' => false, 'message' => 'Érvénytelen biztonsági token. Kérjük frissítse az oldalt és próbálja újra.']);
    exit;
}
// CSRF token felhasználás után újragenerálás (single-use token)
unset($_SESSION['csrf_token']);

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
// Telefonszám formátum ellenőrzés (nemzetközi és hazai formátumok)
if (!empty($phone) && !preg_match('/^\+?[0-9\s\-\(\)\/]{6,20}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Érvénytelen telefonszám formátum.']);
    exit;
}
$message = isset($_POST["message"]) ? trim(strip_tags($_POST["message"])) : "";

// Validáció
$errors = [];

if (empty($name)) {
    $errors[] = "A név megadása kötelező.";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n%]/', $email)) {
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
    "Reply-To: " . str_replace(["\r", "\n", "%0a", "%0d"], '', $email),
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
