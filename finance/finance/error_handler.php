<?php
/**
 * error_handler.php
 * Custom Exception and Error Handling for the Finance ERP
 */

// Centralized error responder
function sendError($message, $code = 400) {
    // If it's an AJAX/REST request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
    
    // Otherwise, show a clean UI error message (could be expanded to a full page)
    die("<div style='padding: 20px; background: #fff5f5; border-left: 5px solid #e53e3e; font-family: sans-serif; margin: 20px; border-radius: 8px;'>
            <h3 style='color: #c53030; margin-top: 0;'>Application Error</h3>
            <p style='color: #4a5568;'>$message</p>
            <a href='dashboard.php' style='color: #3182ce; text-decoration: none; font-weight: bold;'>&larr; Return to Safety</a>
         </div>");
}

// Global Exception Handler
set_exception_handler(function ($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    sendError("A system error occurred. Our team has been notified.", 500);
});

// Global Error Handler
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    // We only show critical errors to users
    if ($errno === E_USER_ERROR) {
        sendError($errstr, 500);
    }
    return true;
});
