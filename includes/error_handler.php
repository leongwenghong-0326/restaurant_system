<?php
// Error handling functions

// Function to log errors
function logError($message, $file = null, $line = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    if ($file && $line) {
        $log_message .= " (File: $file, Line: $line)";
    }
    $log_message .= "\n";
    
    // Log to file
    error_log($log_message, 3, __DIR__ . '/../logs/error.log');
}

// Function to display user-friendly error messages
function displayError($message, $type = 'danger') {
    $allowed_types = ['success', 'info', 'warning', 'danger'];
    if (!in_array($type, $allowed_types)) {
        $type = 'danger'; // default to danger
    }
    
    return '<div class="alert alert-' . $type . '">' . htmlspecialchars($message) . '</div>';
}

// Function to sanitize user input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Set up basic error reporting for development
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development'); // Change to 'production' for production
}

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Don't show errors to users in production
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}
?>