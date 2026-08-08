<?php
/**
 * Seed full employee details for the 7 Byblos demo employees.
 * Updates: job_title_id, marital_status, address (plain)
 *          phone, personal_email, date_of_birth (encrypted via encrypt_field())
 *
 * Usage: php database/seed_employee_details.php
 * Run AFTER seed_byblos.sql has been imported.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Autoloader must come first so App\* classes resolve inside helpers.php
require BASE_PATH . '/vendor/autoload.php';

// Register PSR-4 autoloader for App\ namespace
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file     = BASE_PATH . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Load helpers — also reads .env (sets ENCRYPTION_KEY and DB_* in $_ENV)
require BASE_PATH . '/app/Support/helpers.php';

// ── PDO connection using env values loaded above ──────────────────────────────
$dsn  = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    env('DB_HOST', '127.0.0.1'),
    env('DB_PORT', '3306'),
    env('DB_DATABASE', 'hr_system')
);
$pdo = new PDO($dsn, env('DB_USERNAME', 'root'), env('DB_PASSWORD', ''), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

echo "Connected to DB: " . env('DB_DATABASE') . "\n";

// ── 1. Ensure job titles exist for Byblos ────────────────────────────────────
$jobTitles = [
    'Operations Manager',
    'Finance Manager',
    'Press Operator',
    'HR Assistant',
    'Production Technician',
    'Graphic Designer',
    'Quality Control Specialist',
];

$titleIds = [];
foreach ($jobTitles as $title) {
    // Try to find existing; if not, insert
    $stmt = $pdo->prepare('SELECT id FROM job_titles WHERE name = ? LIMIT 1');
    $stmt->execute([$title]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $titleIds[$title] = (int) $row['id'];
    } else {
        // Generate a short unique code from the title
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $title));
        $code = substr($code, 0, 50);
        $pdo->prepare('INSERT INTO job_titles (name, code) VALUES (?, ?)')
            ->execute([$title, $code]);
        $titleIds[$title] = (int) $pdo->lastInsertId();
    }
    echo "  Job title '{$title}' => id {$titleIds[$title]}\n";
}

// ── 2. Employee detail data ───────────────────────────────────────────────────
$employees = [
    [
        'id'             => 1,
        'job_title'      => 'Operations Manager',
        'marital_status' => 'married',
        'address_line_1' => 'Rue Maarad, Jounieh',
        'city'           => 'Jounieh',
        'country'        => 'Lebanon',
        'phone'          => '+961 3 211 445',
        'personal_email' => 'karim.nassar@gmail.com',
        'date_of_birth'  => '1980-04-15',
    ],
    [
        'id'             => 2,
        'job_title'      => 'Finance Manager',
        'marital_status' => 'single',
        'address_line_1' => 'Rue Sursock, Achrafieh',
        'city'           => 'Beirut',
        'country'        => 'Lebanon',
        'phone'          => '+961 3 344 876',
        'personal_email' => 'lina.khoury@hotmail.com',
        'date_of_birth'  => '1985-09-22',
    ],
    [
        'id'             => 3,
        'job_title'      => 'Press Operator',
        'marital_status' => 'married',
        'address_line_1' => 'Rue Mansourieh, Metn',
        'city'           => 'Metn',
        'country'        => 'Lebanon',
        'phone'          => '+961 3 567 321',
        'personal_email' => 'georges.saade@yahoo.com',
        'date_of_birth'  => '1990-02-08',
    ],
    [
        'id'             => 4,
        'job_title'      => 'HR Assistant',
        'marital_status' => 'single',
        'address_line_1' => 'Rue Hamra, Ras Beirut',
        'city'           => 'Beirut',
        'country'        => 'Lebanon',
        'phone'          => '+961 3 678 543',
        'personal_email' => 'nadia.rahhal@gmail.com',
        'date_of_birth'  => '1996-07-30',
    ],
    [
        'id'             => 5,
        'job_title'      => 'Production Technician',
        'marital_status' => 'married',
        'address_line_1' => 'Rue Baabda, Baabda',
        'city'           => 'Baabda',
        'country'        => 'Lebanon',
        'phone'          => '+961 3 789 654',
        'personal_email' => 'elie.gemayel@gmail.com',
        'date_of_birth'  => '1988-11-12',
    ],
    [
        'id'             => 6,
        'job_title'      => 'Graphic Designer',
        'marital_status' => 'single',
        'address_line_1' => 'Rue Verdun, Ras Beirut',
        'city'           => 'Beirut',
        'country'        => 'Lebanon',
        'phone'          => '+961 3 890 765',
        'personal_email' => 'maya.haddad@gmail.com',
        'date_of_birth'  => '1993-03-25',
    ],
    [
        'id'             => 7,
        'job_title'      => 'Quality Control Specialist',
        'marital_status' => 'married',
        'address_line_1' => 'Rue Antelias, Antelias',
        'city'           => 'Antelias',
        'country'        => 'Lebanon',
        'phone'          => '+961 3 901 876',
        'personal_email' => 'charbel.abn@outlook.com',
        'date_of_birth'  => '1987-06-18',
    ],
];

// ── 3. Update each employee ───────────────────────────────────────────────────
$stmt = $pdo->prepare(
    'UPDATE employees
     SET job_title_id    = :jt,
         marital_status  = :ms,
         address_line_1  = :addr1,
         city            = :city,
         country         = :country,
         phone           = :phone,
         personal_email  = :email,
         date_of_birth   = :dob
     WHERE id = :id'
);

foreach ($employees as $emp) {
    $titleId  = $titleIds[$emp['job_title']];
    $encPhone = encrypt_field($emp['phone']);
    $encEmail = encrypt_field($emp['personal_email']);
    $encDob   = encrypt_field($emp['date_of_birth']);

    $stmt->execute([
        'jt'      => $titleId,
        'ms'      => $emp['marital_status'],
        'addr1'   => $emp['address_line_1'],
        'city'    => $emp['city'],
        'country' => $emp['country'],
        'phone'   => $encPhone,
        'email'   => $encEmail,
        'dob'     => $encDob,
        'id'      => $emp['id'],
    ]);

    echo "  Updated employee {$emp['id']}: {$emp['job_title']} — {$emp['city']}\n";
}

echo "\nDone. All 7 employees updated with full details.\n";
