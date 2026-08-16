<?php
session_start();

$messages = $_SESSION['messages'] ?? [];
$uploadedFiles = $_SESSION['uploaded_files'] ?? [];

unset($_SESSION['messages']);
unset($_SESSION['uploaded_files']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FileVault - Uploaded Files</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --surface: rgba(255, 255, 255, 0.96);
        }
        * { box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 2rem 1rem;
        }
        .glass-panel {
            background: var(--surface);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        .panel-header {
            text-align: center;
            padding: 2.5rem 2rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .panel-body { padding: 2rem; }
        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.35);
        }
        .icon-circle i { color: white; font-size: 32px; }
        .title { color: #111827; font-weight: 800; letter-spacing: -0.02em; }
        .subtitle { color: #6b7280; font-weight: 500; }
        .button {
            border-radius: 12px;
            font-weight: 600;
            padding: 14px 28px;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .button.is-link {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
        }
        .button.is-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        .container { max-width: 1000px; }
        .has-text-centered { text-align: center; }
        .mt-6 { margin-top: 1.5rem; }
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }
        .empty-state i { font-size: 64px; color: #d1d5db; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-panel">
            <div class="panel-header">
                <div class="icon-circle">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="title is-2">Uploaded Files</h1>
                <p class="subtitle is-5">Review and preview your uploaded files</p>
            </div>

            <div class="panel-body">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['type']; ?>">
                            <i class="fas fa-<?php echo $msg['type'] === 'success' ? 'check-circle' : ($msg['type'] === 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                            <?php echo htmlspecialchars($msg['text']); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (empty($uploadedFiles)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3 class="title is-4">No Files Uploaded Yet</h3>
                        <p class="subtitle is-6">Upload files from the form to see them here.</p>
                        <a href="index.php" class="button is-link mt-4">
                            <i class="fas fa-upload"></i>
                            <span>Go to Upload</span>
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($uploadedFiles as $file): ?>
                        <div class="file-card">
                            <div class="file-header">
                                <div class="file-icon <?php echo $file['category']; ?>">
                                    <i class="fas fa-<?php echo $file['category'] === 'pdf' ? 'file-pdf' : ($file['category'] === 'audio' ? 'music' : ($file['category'] === 'image' ? 'image' : 'file-video')); ?>"></i>
                                </div>
                                <div class="file-info">
                                    <h4><?php echo htmlspecialchars($file['name']); ?></h4>
                                    <p><?php echo strtoupper($file['category']); ?> File &bull; <?php echo round($file['size'] / 1024, 2); ?> KB</p>
                                </div>
                            </div>

                            <div class="preview-container">
                                <?php if ($file['category'] === 'pdf'): ?>
                                    <embed src="<?php echo htmlspecialchars($file['path']); ?>" type="application/pdf" />
                                <?php elseif ($file['category'] === 'audio'): ?>
                                    <audio controls>
                                        <source src="<?php echo htmlspecialchars($file['path']); ?>" type="<?php echo htmlspecialchars($file['mime']); ?>">
                                        Your browser does not support the audio element.
                                    </audio>
                                <?php elseif ($file['category'] === 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($file['path']); ?>" alt="<?php echo htmlspecialchars($file['name']); ?>" />
                                <?php elseif ($file['category'] === 'video'): ?>
                                    <video controls>
                                        <source src="<?php echo htmlspecialchars($file['path']); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php endif; ?>
                            </div>

                            <div class="has-text-centered">
                                <a href="<?php echo htmlspecialchars($file['path']); ?>" download class="download-btn <?php echo $file['category']; ?>">
                                    <i class="fas fa-download"></i>
                                    <span>Download</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="has-text-centered mt-6">
                    <a href="index.php" class="button is-link">
                        <i class="fas fa-upload"></i>
                        <span>Upload More Files</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
