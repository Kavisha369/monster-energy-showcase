<?php
/**
 * Export Subscribers CSV
 */
require_once 'config.php';
require_once 'auth.php';

// Authentication Check
requireAdmin();

// CSRF check for GET request
$csrfToken = $_GET['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Access Denied: CSRF validation failed.';
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT email, subscribed_at FROM subscriptions ORDER BY subscribed_at DESC");
    
    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=monster_subscribers_' . date('Y-m-d') . '.csv');
    
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // Output column headings
    fputcsv($output, ['Email Address', 'Subscription Date']);
    
    // Output data rows
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['email'],
            $row['subscribed_at']
        ]);
    }
    
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Database error occurred during export.';
    exit;
}
