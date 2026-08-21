<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    
    if (!empty($title) && isset($_FILES['pdf_file'])) {
        $file = $_FILES['pdf_file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $filename = uniqid() . '_' . basename($file['name']);
            $target_path = UPLOAD_DIR . $filename;
            
            // Check if file is PDF
            $file_type = mime_content_type($file['tmp_name']);
            if ($file_type === 'application/pdf') {
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    saveNotification($title, $filename, date('Y-m-d H:i:s'));
                    $message = 'Notification uploaded successfully!';
                } else {
                    $error = 'Error uploading file.';
                }
            } else {
                $error = 'Only PDF files are allowed.';
            }
        } else {
            $error = 'Error uploading file.';
        }
    } else {
        $error = 'Please fill all fields.';
    }
}

$notifications = getNotifications();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - The Eagle Association</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <h1 class="old-english">The Eagle Association</h1>
            <div class="admin-section">
                <span class="admin-welcome">Welcome, Admin</span>
                <a href="index.php" class="btn btn-home"><i class="fas fa-home"></i> Home</a>
                <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>

        <main class="admin-main">
            <div class="admin-panel">
                <h2><i class="fas fa-cogs"></i> Admin Panel</h2>
                
                <?php if (isset($message)): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="admin-grid">
                    <div class="upload-section">
                        <h3><i class="fas fa-upload"></i> Upload New Notification</h3>
                        <form method="POST" enctype="multipart/form-data" class="upload-form">
                            <div class="form-group">
                                <label for="title">Notification Title</label>
                                <input type="text" id="title" name="title" required placeholder="Enter notification title">
                            </div>
                            
                            <div class="form-group">
                                <label for="pdf_file">PDF File</label>
                                <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required>
                                <small>Only PDF files are allowed (Max: 10MB)</small>
                            </div>
                            
                            <button type="submit" class="btn btn-upload">
                                <i class="fas fa-upload"></i> Upload Notification
                            </button>
                        </form>
                    </div>
                    
                    <div class="stats-section">
                        <h3><i class="fas fa-chart-bar"></i> Statistics</h3>
                        <div class="stats">
                            <div class="stat-card">
                                <i class="fas fa-file-alt"></i>
                                <h4><?php echo count($notifications); ?></h4>
                                <p>Total Notifications</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-folder-open"></i>
                                <h4><?php echo count(glob(UPLOAD_DIR . "*.pdf")); ?></h4>
                                <p>PDF Files</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="notifications-list">
                    <h3><i class="fas fa-list"></i> All Notifications (<?php echo count($notifications); ?>)</h3>
                    
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No notifications uploaded yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="notifications-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>File Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($notifications) as $notification): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($notification['date'])); ?></td>
                                        <td><?php echo htmlspecialchars($notification['filename']); ?></td>
                                        <td class="actions">
                                            <a href="<?php echo UPLOAD_DIR . $notification['filename']; ?>" 
                                               class="btn btn-view" 
                                               target="_blank">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <form action="delete.php" method="POST" class="inline-form">
                                                <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="btn btn-delete" onclick="return confirm('Delete this notification?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>