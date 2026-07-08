<?php
session_start();

// Configuration
$USER_DB = __DIR__ . DIRECTORY_SEPARATOR . 'users.json';

// GitHub Configurations (তোর দেওয়া তথ্য অনুযায়ী সরাসরি যুক্ত করা হলো)
define('GH_TOKEN', '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');
define('GH_USER', 'debjit903');
define('GH_REPO', 'eoffice');

if (!file_exists($USER_DB)) {
    $default_users = ["admin" => ["hash" => password_hash("ad@1234", PASSWORD_DEFAULT)]];
    file_put_contents($USER_DB, json_encode($default_users, JSON_PRETTY_PRINT));
}

// GitHub API Helper Functions
function github_file_exists($filename) {
    $url = "https://api.github.com/repos/" . GH_USER . "/" . GH_REPO . "/contents/" . urlencode($filename);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token " . GH_TOKEN,
        "User-Agent: eOffice-App"
    ]);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $http_code === 200;
}

function github_get_file_sha($filename) {
    $url = "https://api.github.com/repos/" . GH_USER . "/" . GH_REPO . "/contents/" . urlencode($filename);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token " . GH_TOKEN,
        "User-Agent: eOffice-App"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['sha'] ?? null;
}

function github_upload_file($filename, $content, $sha = null) {
    $url = "https://api.github.com/repos/" . GH_USER . "/" . GH_REPO . "/contents/" . urlencode($filename);
    $data = [
        "message" => "eOffice File Operation",
        "content" => base64_encode($content)
    ];
    if ($sha) {
        $data['sha'] = $sha;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token " . GH_TOKEN,
        "User-Agent: eOffice-App",
        "Content-Type: application/json"
    ]);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($http_code === 200 || $http_code === 201);
}

function github_delete_file($filename) {
    $sha = github_get_file_sha($filename);
    if (!$sha) return false;
    
    $url = "https://api.github.com/repos/" . GH_USER . "/" . GH_REPO . "/contents/" . urlencode($filename);
    $data = [
        "message" => "eOffice Delete Operation",
        "sha" => $sha
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token " . GH_TOKEN,
        "User-Agent: eOffice-App",
        "Content-Type: application/json"
    ]);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $http_code === 200;
}

function github_list_files() {
    $url = "https://api.github.com/repos/" . GH_USER . "/" . GH_REPO . "/contents/";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token " . GH_TOKEN,
        "User-Agent: eOffice-App"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    $files = [];
    if (is_array($data)) {
        foreach ($data as $item) {
            if ($item['type'] === 'file' && $item['name'] !== 'vercel.json' && $item['name'] !== 'index.php' && $item['name'] !== 'users.json') {
                $files[] = $item['name'];
            }
        }
    }
    return $files;
}

$error_msg = "";
$success_msg = "";

// Auth Actions
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (file_exists($USER_DB)) {
        $users = json_decode(file_get_contents($USER_DB), true);
        if (isset($users[$username]) && password_verify($password, $users[$username]['hash'])) {
            $_SESSION['user'] = $username;
            header("Location: index.php");
            exit;
        }
    }
    $error_msg = "Invalid username or password.";
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// File Operations Context
if (isset($_SESSION['user'])) {
    $current_user = $_SESSION['user'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $filename = trim($_POST['filename'] ?? '');
                if (!empty($filename)) {
                    if (!github_file_exists($filename)) {
                        if (github_upload_file($filename, "")) {
                            $success_msg = "Blank file initialized successfully on GitHub.";
                        } else { $error_msg = "Failed to create file on GitHub."; }
                    } else { $error_msg = "File already exists."; }
                }
                break;

            case 'upload':
                if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
                    $original_name = basename($_FILES['uploaded_file']['name']);
                    $content = file_get_contents($_FILES['uploaded_file']['tmp_name']);
                    
                    if (github_upload_file($original_name, $content)) {
                        $success_msg = "File '$original_name' uploaded successfully to GitHub.";
                    } else { $error_msg = "Failed to upload file to GitHub."; }
                } else { $error_msg = "No file selected or upload error encountered."; }
                break;

            case 'save_notepad':
                $filename = $_POST['filename'] ?? '';
                $content = $_POST['content'] ?? '';
                $sha = github_get_file_sha($filename);
                if ($sha) {
                    if (github_upload_file($filename, $content, $sha)) {
                        $success_msg = "Changes written directly to GitHub.";
                    } else { $error_msg = "Failed to update file."; }
                }
                break;

            case 'rename':
                $old_name = $_POST['old_name'] ?? '';
                $new_name = trim($_POST['new_name'] ?? '');
                if (!empty($new_name) && $old_name !== $new_name) {
                    $old_url = "https://raw.githubusercontent.com/" . GH_USER . "/" . GH_REPO . "/main/" . urlencode($old_name);
                    $content = @file_get_contents($old_url);
                    if ($content !== false) {
                        if (github_upload_file($new_name, $content)) {
                            github_delete_file($old_name);
                            $success_msg = "File altered successfully.";
                        } else { $error_msg = "Failed to rename file."; }
                    }
                } else { $error_msg = "Name change conflict detected."; }
                break;

            case 'delete':
                $filename = $_POST['filename'] ?? '';
                if (github_delete_file($filename)) {
                    $success_msg = "File erased permanently from GitHub.";
                } else { $error_msg = "Failed to delete file."; }
                break;
                
            case 'change_password':
                $new_password = $_POST['new_password'] ?? '';
                if (strlen($new_password) >= 6) {
                    $users = json_decode(file_get_contents($USER_DB), true);
                    $users[$current_user]['hash'] = password_hash($new_password, PASSWORD_DEFAULT);
                    file_put_contents($USER_DB, json_encode($users, JSON_PRETTY_PRINT));
                    $success_msg = "Security key modified.";
                } else { $error_msg = "Password must be >= 6 characters."; }
                break;
        }
    }
}

// Logic to determine how to stream or open the selected file
$active_file = $_GET['open_file'] ?? '';
$file_extension = strtolower(pathinfo($active_file, PATHINFO_EXTENSION));

$edit_file_content = "";
$is_editable_text = in_array($file_extension, ['txt', 'json', 'md', 'html', 'css', 'js', '']);
$is_image = in_array($file_extension, ['png', 'jpg', 'jpeg', 'webp', 'gif']);
$is_video = in_array($file_extension, ['mp4', 'webm']);
$is_audio = in_array($file_extension, ['mp3', 'wav']);

if (!empty($active_file)) {
    $file_web_url = "https://raw.githubusercontent.com/" . GH_USER . "/" . GH_REPO . "/main/" . urlencode($active_file);
    if ($is_editable_text) {
        $ctx = stream_context_create([
            "http" => ["header" => "Authorization: token " . GH_TOKEN . "\r\nUser-Agent: eOffice-App\r\n"]
        ]);
        $edit_file_content = @file_get_contents($file_web_url, false, $ctx);
        if ($edit_file_content === false) {
            $active_file = "";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eOffice - Enterprise Workstation Console</title>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <style>
        :root {
            --bg-main: #f3f4f6;
            --panel-bg: #ffffff;
            --text-dark: #1f2937;
            --text-gray: #4b5563;
            --primary: #2563eb;
            --border: #e5e7eb;
            --danger: #dc2626;
            --scrollbar-thumb: #cbd5e1;
            --scrollbar-thumb-hover: #94a3b8;
        }
        
        html, body { 
            background: var(--bg-main); color: var(--text-dark); margin: 0; padding: 0; 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            height: 100%; overflow-y: auto;
        }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: var(--bg-main); }
        ::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 5px; border: 2px solid var(--bg-main); }
        ::-webkit-scrollbar-thumb:hover { background: var(--scrollbar-thumb-hover); }
        * { scrollbar-width: thin; scrollbar-color: var(--scrollbar-thumb) var(--bg-main); box-sizing: border-box; }

        .login-wrap { max-width: 400px; margin: 100px auto; width: 90%; background: var(--panel-bg); padding: 32px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid var(--border); }
        .app-layout { display: flex; flex-direction: column; width: 100%; min-height: 100%; }
        .top-navbar { height: 60px; background: var(--panel-bg); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; padding: 0 24px; flex-shrink: 0; }
        .brand { font-weight: 700; font-size: 18px; color: #111827; }
        .brand span { background: var(--primary); color: white; padding: 3px 8px; border-radius: 6px; font-size: 11px; margin-left: 5px;}
        
        .main-workspace { display: flex; flex: 1; }
        .sidebar { width: 340px; background: var(--panel-bg); border-right: 1px solid var(--border); display: flex; flex-direction: column; }
        .content-pane { flex: 1; background: #fafafa; padding: 24px; display: flex; flex-direction: column; }

        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: var(--text-gray); }
        input[type="text"], input[type="password"], textarea { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; background: #fff; }
        
        .btn { background: var(--primary); color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;}
        .btn-secondary { background: transparent; color: var(--text-gray); border: 1px solid var(--border); }
        .btn-danger { background: var(--danger); }

        .alert-banner { padding: 12px 24px; font-size: 14px; display: flex; justify-content: space-between; background: #dcfce7; color: #16a34a; }
        .alert-banner.error { background: #fee2e2; color: var(--danger); }

        .sidebar-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .file-list { flex: 1; list-style: none; padding: 0; margin: 0; }
        .file-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 1px solid var(--border); position: relative; }
        .file-item:hover { background: #f9fafb; }
        .file-info-block { display: flex; flex-direction: column; text-decoration: none; max-width: 80%; }
        .file-title { font-size: 14px; font-weight: 500; color: var(--text-dark); word-break: break-all; }

        .dot-menu-trigger { background: none; border: none; font-size: 20px; color: #9ca3af; cursor: pointer; width: 32px; height: 32px; border-radius: 50%; }
        .dropdown-panel { display: none; position: absolute; right: 20px; top: 44px; background: white; border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); z-index: 50; width: 130px; }
        .dropdown-panel a, .dropdown-panel button { display: block; width: 100%; text-align: left; padding: 8px 12px; font-size: 13px; color: var(--text-dark); text-decoration: none; background: none; border: none; cursor: pointer; }
        .dropdown-panel a:hover, .dropdown-panel button:hover { background: #f3f4f6; }

        .workspace-viewer-card { background: var(--panel-bg); border: 1px solid var(--border); border-radius: 10px; flex: 1; display: flex; flex-direction: column; min-height: 480px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .viewer-header { padding: 14px 20px; background: #fafafa; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 600; }
        .viewer-body-canvas { flex: 1; min-height: 400px; position: relative; background: #ffffff; overflow: auto; }
        
        .embedded-office-frame { width: 100%; height: 100%; border: none; position: absolute; top:0; left:0; }
        .editor-textarea { width: 100%; height: 100%; border: none; resize: none; font-family: monospace; font-size: 14px; padding: 20px; position: absolute; top:0; left:0; }
        .editor-textarea:focus { outline: none; }
        .viewer-footer { padding: 12px 20px; border-top: 1px solid var(--border); background: #fafafa; display: flex; justify-content: flex-end; }

        .placeholder-screen { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; min-height: 400px; color: #9ca3af; font-size: 14px; text-align: center; padding: 20px; }

        .excel-table-render { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; background: #fff; }
        .excel-table-render th { background: #f8fafc; color: #64748b; padding: 10px; border: 1px solid #e2e8f0; font-weight: 600; }
        .excel-table-render td { padding: 10px; border: 1px solid #e2e8f0; color: #334155; }
        .excel-table-render tr:nth-child(even) { background: #f8fafc; }

        .media-image-canvas { max-width: 100%; max-height: 85vh; display: block; margin: auto; padding: 15px; border-radius: 4px; object-fit: contain; }
        .media-video-canvas { width: 100%; height: 100%; background: #000; outline: none; }
        .media-audio-container { display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100%; padding: 40px; background: #f8fafc; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 100; align-items: center; justify-content: center; }
        .modal-card { background: white; border-radius: 10px; width: 100%; max-width: 420px; padding: 24px; }
        .modal-buttons-row { display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px; }
        .modal-divider { margin: 20px 0; border: 0; border-top: 1px dashed var(--border); text-align: center; height: 0; }
        .modal-divider-text { background: #fff; padding: 0 8px; position: relative; top: -11px; font-size: 12px; color: var(--text-gray); font-weight: 600; }

        @media (max-width: 768px) {
            .main-workspace { flex-direction: column; }
            .sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--border); }
            .content-pane { padding: 12px; }
        }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['user'])): ?>
    <div class="login-wrap">
        <h2 style="text-align:center; margin-bottom:20px;">eOffice Console</h2>
        <?php if($error_msg): ?><div style="color:var(--danger); font-size:13px; text-align:center; margin-bottom:10px;"><?= $error_msg ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" class="btn" style="width:100%">Login</button>
        </form>
    </div>
<?php else: ?>
    <div class="app-layout">
        <header class="top-navbar">
            <div class="brand">eOffice Workspace<span>PRO</span></div>
            <div style="display:flex; align-items:center; gap:12px;">
                <button onclick="openModal('pwdModal')" class="btn btn-secondary" style="padding:6px 12px; font-size:12px;">Security Key</button>
                <a href="index.php?action=logout" class="btn btn-danger" style="padding:6px 12px; font-size:12px; text-decoration:none;">Logout</a>
            </div>
        </header>

        <?php if($error_msg || $success_msg): ?>
            <div class="alert-banner <?= $error_msg ? 'error' : '' ?>">
                <span><?= $error_msg ? $error_msg : $success_msg ?></span>
                <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; font-weight:bold; cursor:pointer;">&times;</button>
            </div>
        <?php endif; ?>

        <div class="main-workspace">
            <aside class="sidebar">
                <div class="sidebar-header">
                    <h3 style="font-size:14px;">Files</h3>
                    <button onclick="openModal('createModal')" class="btn" style="padding:6px 12px; font-size:12px;">Add File</button>
                </div>
                <ul class="file-list">
                    <?php
                    $entries = github_list_files();
                    if (empty($entries)):
                        echo "<li style='padding:20px; font-size:13px; color:#9ca3af; text-align:center;'>No files in directory.</li>";
                    else:
                        foreach ($entries as $index => $name):
                            ?>
                            <li class="file-item">
                                <a href="index.php?open_file=<?= urlencode($name) ?>" class="file-info-block">
                                    <span class="file-title">📁 <?= htmlspecialchars($name) ?></span>
                                </a>
                                <div>
                                    <button class="dot-menu-trigger" onclick="toggleDropdown(event, 'dd-<?= $index ?>')">&#8942;</button>
                                    <div id="dd-<?= $index ?>" class="dropdown-panel">
                                        <a href="index.php?open_file=<?= urlencode($name) ?>">Open File</a>
                                        <button type="button" onclick="triggerRename('<?= htmlspecialchars($name) ?>')">Rename</button>
                                        <button type="button" style="color:var(--danger)" onclick="triggerDelete('<?= htmlspecialchars($name) ?>')">Delete</button>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; 
                    endif; ?>
                </ul>
            </aside>

            <main class="content-pane">
                <div class="workspace-viewer-card">
                    <?php if (empty($active_file)): ?>
                        <div class="placeholder-screen">
                            <p style="font-size: 24px; margin-bottom: 8px;">📁</p>
                            <p>Select a document from the file sidebar to view or edit instantly inside this canvas viewport.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="index.php?open_file=<?= urlencode($active_file) ?>" style="display:flex; flex-direction:column; height:100%; margin:0;">
                            <input type="hidden" name="action" value="save_notepad">
                            <div class="viewer-header">
                                <span>Active File: <?= htmlspecialchars($active_file) ?></span>
                                <input type="hidden" name="filename" value="<?= htmlspecialchars($active_file) ?>">
                            </div>
                            
                            <div class="viewer-body-canvas" id="canvas-container">
                                <?php if ($is_editable_text): ?>
                                    <textarea name="content" class="editor-textarea"><?= htmlspecialchars($edit_file_content) ?></textarea>
                                
                                <?php elseif ($is_image): ?>
                                    <img src="<?= $file_web_url ?>" class="media-image-canvas" alt="Render Asset">

                                <?php elseif ($is_video): ?>
                                    <video src="<?= $file_web_url ?>" controls class="media-video-canvas"></video>

                                <?php elseif ($is_audio): ?>
                                    <div class="media-audio-container">
                                        <p style="font-size:42px; margin-bottom:10px;">🎵</p>
                                        <p style="font-size:14px; font-weight:600; margin-bottom:15px; color:var(--text-gray);"><?= htmlspecialchars($active_file) ?></p>
                                        <audio src="<?= $file_web_url ?>" controls style="width:100%; max-width:400px;"></audio>
                                    </div>

                                <?php elseif ($file_extension === 'pdf'): ?>
                                    <iframe class="embedded-office-frame" src="<?= $file_web_url ?>"></iframe>

                                <?php elseif (in_array($file_extension, ['docx', 'pptx', 'doc'])): ?>
                                    <div class="placeholder-screen" style="justify-content: flex-start; padding-top: 50px;">
                                        <p style="font-size: 32px; margin-bottom: 12px;">📝</p>
                                        <p style="font-size: 16px; font-weight:600; color:var(--text-dark); margin-bottom:15px;">Open with alternative workflows:</p>
                                        <div style="display:flex; gap:10px;">
                                            <a href="https://docs.google.com/gview?url=<?= urlencode($file_web_url) ?>&embedded=true" class="btn" target="_blank">Open via Google Engine Mirror</a>
                                            <a href="<?= $file_web_url ?>" download class="btn btn-secondary">Download directly to device</a>
                                        </div>
                                    </div>

                                <?php elseif (in_array($file_extension, ['xlsx', 'xls'])): ?>
                                    <div id="excel-render-target" style="padding: 15px;">
                                        <p style="color:var(--text-gray); font-size:13px; text-align:center;">Parsing localized binary row matrices...</p>
                                    </div>
                                    <script>
                                        fetch('<?= $file_web_url ?>')
                                            .then(res => res.arrayBuffer())
                                            .then(data => {
                                                var workbook = XLSX.read(new Uint8Array(data), {type: 'array'});
                                                var firstSheetName = workbook.SheetNames[0];
                                                var worksheet = workbook.Sheets[firstSheetName];
                                                var htmlStr = XLSX.utils.sheet_to_html(worksheet, { id: "excel-table", editable: false });
                                                var target = document.getElementById('excel-render-target');
                                                target.innerHTML = htmlStr;
                                                var table = target.querySelector('table');
                                                if(table) table.className = "excel-table-render";
                                            })
                                            .catch(err => {
                                                document.getElementById('excel-render-target').innerHTML = 
                                                    '<div class="placeholder-screen">' +
                                                    '<p>Could not build offline grid engine. You can download the file directly:</p>' +
                                                    '<a href="<?= $file_web_url ?>" download class="btn" style="margin-top:10px;">Open with Microsoft Excel</a>' +
                                                    '</div>';
                                            });
                                    </script>
                                <?php else: ?>
                                    <div class="placeholder-screen">
                                        <p>Preview mapping not supported for raw structural extension (.<?= $file_extension ?>).</p>
                                        <a href="<?= $file_web_url ?>" download style="margin-top:10px; color:var(--primary);">Download File Raw</a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="viewer-footer">
                                <?php if ($is_editable_text): ?>
                                    <button type="submit" class="btn">Save Document Content</button>
                                <?php else: ?>
                                    <span style="font-size:12px; color:var(--text-gray);">Read-Only Workspace View Mode</span>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <div id="createModal" class="modal-overlay">
        <div class="modal-card">
            <h3 style="margin-bottom: 12px;">Add File to Directory</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="form-group">
                    <label>Upload Document (.pdf, .xlsx, .docx, images, media, etc.)</label>
                    <input type="file" name="uploaded_file" required style="border:none; padding:6px 0;">
                </div>
                <button type="submit" class="btn" style="width:100%">Upload Selected File</button>
            </form>

            <div class="modal-divider">
                <span class="modal-divider-text">OR CREATE BLANK FILE</span>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Target Designation Filename</label>
                    <input type="text" name="filename" placeholder="notes.txt" autocomplete="off">
                </div>
                <div class="modal-buttons-row">
                    <button type="button" onclick="closeModal('createModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn">Create File</button>
                </div>
            </form>
        </div>
    </div>

    <div id="renameModal" class="modal-overlay">
        <div class="modal-card">
            <h3>Rename Document</h3>
            <form method="POST">
                <input type="hidden" name="action" value="rename"><input type="hidden" name="old_name" id="rename-old-target">
                <div class="form-group"><label>New Target Filename</label><input type="text" name="new_name" id="rename-new-input" required autocomplete="off"></div>
                <div class="modal-buttons-row"><button type="button" onclick="closeModal('renameModal')" class="btn btn-secondary">Cancel</button><button type="submit" class="btn">Apply</button></div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal-card">
            <h3 style="color:var(--danger)">Delete File</h3>
            <p>Are you completely sure you want to permanently delete this file structure?</p>
            <form method="POST" style="margin-top:15px;">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="filename" id="delete-target-val">
                <div class="modal-buttons-row"><button type="button" onclick="closeModal('deleteModal')" class="btn btn-secondary">Cancel</button><button type="submit" class="btn" style="background:var(--danger)">Confirm Erase</button></div>
            </form>
        </div>
    </div>

    <div id="pwdModal" class="modal-overlay">
        <div class="modal-card">
            <h3>Change Passphrase</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group"><label>New Code</label><input type="password" name="new_password" required minlength="6"></div>
                <div class="modal-buttons-row"><button type="button" onclick="closeModal('pwdModal')" class="btn btn-secondary">Cancel</button><button type="submit" class="btn">Save</button></div>
            </form>
        </div>
    </div>

    <script>
        function toggleDropdown(event, id) {
            event.stopPropagation();
            document.querySelectorAll('.dropdown-panel').forEach(p => { if(p.id !== id) p.style.display = 'none'; });
            const menu = document.getElementById(id);
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        }
        window.onclick = function() { document.querySelectorAll('.dropdown-panel').forEach(p => p.style.display = 'none'); }
        function openModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        function triggerRename(name) {
            document.getElementById('rename-old-target').value = name;
            document.getElementById('rename-new-input').value = name;
            openModal('renameModal');
        }
        function triggerDelete(name) {
            document.getElementById('delete-target-val').value = name;
            openModal('deleteModal');
        }
    </script>
<?php endif; ?>
</body>
</html>
