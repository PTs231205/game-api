<?php
/**
 * Reset Master Admin password (CLI only).
 *
 * Usage:
 *   php tools/reset_master_password.php
 *
 * This will:
 * - generate a new random password
 * - update config/master.php password_hash
 * - print the new password to stdout
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Forbidden\n";
    exit(1);
}

$root = realpath(__DIR__ . '/..');
if (!$root) {
    fwrite(STDERR, "Unable to resolve project root\n");
    exit(1);
}

$masterConfigPath = $root . '/config/master.php';
if (!is_file($masterConfigPath)) {
    fwrite(STDERR, "Missing file: {$masterConfigPath}\n");
    exit(1);
}

$creds = require $masterConfigPath;
$username = (string)($creds['username'] ?? 'admin');

// Generate new password
$newPassword = 'MA_' . bin2hex(random_bytes(6)) . '!'; // e.g. MA_a1b2c3d4e5f6!
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

$newConfig = "<?php\n"
    . "// Master admin credentials (change immediately).\n"
    . "return [\n"
    . "    'username' => " . var_export($username, true) . ",\n"
    . "    'password_hash' => " . var_export($newHash, true) . ",\n"
    . "];\n";

if (file_put_contents($masterConfigPath, $newConfig, LOCK_EX) === false) {
    fwrite(STDERR, "Failed to write: {$masterConfigPath}\n");
    exit(1);
}

echo "Master username: {$username}\n";
echo "New master password: {$newPassword}\n";
echo "Login URL: /master/login\n";

