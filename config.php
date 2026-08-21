<?php
session_start();

// Configuration
define('UPLOAD_DIR', 'uploads/');
define('DB_FILE', 'database.json');
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('eagle123', PASSWORD_DEFAULT)); // Change this password

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Initialize database if it doesn't exist
if (!file_exists(DB_FILE)) {
    file_put_contents(DB_FILE, json_encode(['notifications' => [], 'users' => []]));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}

// Get all notifications
function getNotifications() {
    $data = json_decode(file_get_contents(DB_FILE), true);
    return $data['notifications'] ?? [];
}

// Save notification
function saveNotification($title, $filename, $date) {
    $data = json_decode(file_get_contents(DB_FILE), true);
    $data['notifications'][] = [
        'id' => uniqid(),
        'title' => $title,
        'filename' => $filename,
        'date' => $date
    ];
    file_put_contents(DB_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// Delete notification
function deleteNotification($id) {
    $data = json_decode(file_get_contents(DB_FILE), true);
    $notifications = $data['notifications'];
    
    foreach ($notifications as $key => $notification) {
        if ($notification['id'] === $id) {
            // Delete the file
            $filepath = UPLOAD_DIR . $notification['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            unset($data['notifications'][$key]);
            break;
        }
    }
    
    $data['notifications'] = array_values($data['notifications']);
    file_put_contents(DB_FILE, json_encode($data, JSON_PRETTY_PRINT));
}
?>