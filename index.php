<?php
require_once 'config.php';
$notifications = getNotifications();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Eagle Association</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1 class="old-english">The Eagle Association</h1>
            <div class="admin-section">
                <?php if (isLoggedIn()): ?>
                    <a href="admin.php" class="btn btn-admin"><i class="fas fa-cog"></i> Admin Panel</a>
                    <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-login"><i class="fas fa-lock"></i> Admin Login</a>
                <?php endif; ?>
            </div>
        </header>

        <main>
            <div class="hero">
                <h2>Association Notifications</h2>
                <p>All official announcements and updates from The Eagle Association</p>
            </div>

            <section class="notifications">
                <h3><i class="fas fa-bullhorn"></i> Latest Notifications</h3>
                
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No notifications available yet.</p>
                    </div>
                <?php else: ?>
                    <div class="notification-grid">
                        <?php foreach (array_reverse($notifications) as $notification): ?>
                            <div class="notification-card">
                                <div class="card-header">
                                    <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                    <span class="date"><?php echo date('F j, Y', strtotime($notification['date'])); ?></span>
                                </div>
                                <div class="card-body">
                                    <p>Official notification document</p>
                                    <div class="file-info">
                                        <i class="fas fa-file-pdf"></i>
                                        <span>PDF Document</span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a href="<?php echo UPLOAD_DIR . $notification['filename']; ?>" 
                                       class="btn btn-download" 
                                       target="_blank" 
                                       download>
                                        <i class="fas fa-download"></i> Download PDF
                                    </a>
                                    <?php if (isLoggedIn()): ?>
                                        <form action="delete.php" method="POST" class="delete-form">
                                            <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" class="btn btn-delete" onclick="return confirm('Delete this notification?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> The Eagle Association. All rights reserved.</p>
        </footer>
    </div>

    <script src="js/script.js"></script>
</body>
</html>