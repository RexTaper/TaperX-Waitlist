<?php
// ─────────────────────────────────────────────
// TaperX Waitlist — Form Submission Handler
// ─────────────────────────────────────────────

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'taperhsn_taperx_waitlist');
define('DB_USER', 'taperhsn_Rex');
define('DB_PASS', 'Duster1984!');

// Allow requests from your own domain only
header('Content-Type: application/json');

// ── Create database connection ──
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}

// ── Create the waitlist table if it doesn't exist ──
$pdo->exec("
    CREATE TABLE IF NOT EXISTS waitlist (
        id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name                VARCHAR(255)  NOT NULL,
        email               VARCHAR(255)  NOT NULL,
        price               VARCHAR(50)   DEFAULT NULL,
        coach_importance    VARCHAR(50)   DEFAULT NULL,
        rx_pharmacy         VARCHAR(10)   DEFAULT NULL,
        tracker_importance  VARCHAR(50)   DEFAULT NULL,
        medication          VARCHAR(500)  DEFAULT NULL,
        medication_other    VARCHAR(255)  DEFAULT NULL,
        notes               TEXT          DEFAULT NULL,
        source              VARCHAR(50)   DEFAULT 'main-form',
        submitted_at        DATETIME      DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── Only accept POST requests ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// ── Read and decode JSON body (sent by the existing JS) ──
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Fall back to regular POST fields if JSON wasn't sent
if (!$data) {
    $data = $_POST;
}

// ── Validate required fields ──
$name  = trim($data['name']  ?? '');
$email = trim($data['email'] ?? '');

if (!$name || !$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name and email are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
    exit;
}

// ── Sanitize optional fields ──
$price              = trim($data['price']              ?? '');
$coach_importance   = trim($data['coach_importance']   ?? '');
$rx_pharmacy        = trim($data['rx_pharmacy']        ?? '');
$tracker_importance = trim($data['tracker_importance'] ?? '');
$medication         = trim($data['medication']         ?? '');
$medication_other   = trim($data['medication_other']   ?? '');
$notes              = trim($data['notes']              ?? '');
$source             = trim($data['source']             ?? 'main-form');

// ── Insert into database (ignore duplicate emails) ──
try {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO waitlist
            (name, email, price, coach_importance, rx_pharmacy,
             tracker_importance, medication, medication_other, notes, source)
        VALUES
            (:name, :email, :price, :coach_importance, :rx_pharmacy,
             :tracker_importance, :medication, :medication_other, :notes, :source)
    ");

    $stmt->execute([
        ':name'               => $name,
        ':email'              => $email,
        ':price'              => $price              ?: null,
        ':coach_importance'   => $coach_importance   ?: null,
        ':rx_pharmacy'        => $rx_pharmacy        ?: null,
        ':tracker_importance' => $tracker_importance ?: null,
        ':medication'         => $medication         ?: null,
        ':medication_other'   => $medication_other   ?: null,
        ':notes'              => $notes              ?: null,
        ':source'             => $source,
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not save your submission.']);
}
