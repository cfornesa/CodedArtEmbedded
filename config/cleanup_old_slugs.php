<?php
/**
 * Cleanup Old Deleted Pieces
 *
 * This script permanently deletes art pieces that have been soft-deleted
 * for longer than the reservation period.
 *
 * Run via cron job:
 * 0 2 * * * /usr/bin/php /path/to/cleanup_old_slugs.php
 * (Runs daily at 2 AM)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/slug_utils.php';
require_once __DIR__ . '/helpers.php';

// Only allow CLI execution for security
if (PHP_SAPI !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
    die('This script can only be run from command line');
}

echo "=========================================\n";
echo "  Slug Cleanup Job\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "=========================================\n\n";

// Get last cleanup time
$lastCleanup = getSiteConfig('last_slug_cleanup', null);
if ($lastCleanup) {
    echo "Last cleanup: " . $lastCleanup . "\n";
}

// Get reservation period
$reservationDays = getSiteConfig('slug_reservation_days', 30);
echo "Reservation period: {$reservationDays} days\n";
echo "\n";

// Run cleanup
echo "Scanning for expired pieces...\n";

$totalDeleted = cleanupOldDeletedPieces('all');

if ($totalDeleted > 0) {
    echo "✓ Permanently deleted {$totalDeleted} expired piece(s)\n";
} else {
    echo "✓ No expired pieces found\n";
}

// Also cleanup old redirects (optional - redirects older than 1 year)
echo "\nCleaning up old redirects...\n";

$pdo = getDBConnection();
$oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));

$stmt = $pdo->prepare("SELECT COUNT(*) FROM slug_redirects WHERE created_at < ?");
$stmt->execute([$oneYearAgo]);
$oldRedirects = $stmt->fetchColumn();

if ($oldRedirects > 0) {
    $stmt = $pdo->prepare("DELETE FROM slug_redirects WHERE created_at < ? AND redirect_count = 0");
    $stmt->execute([$oneYearAgo]);
    $deletedRedirects = $stmt->rowCount();

    echo "✓ Deleted {$deletedRedirects} unused redirect(s) older than 1 year\n";
} else {
    echo "✓ No old redirects to clean up\n";
}

echo "\n";
echo "=========================================\n";
echo "  Cleanup Complete\n";
echo "=========================================\n";

exit(0);
