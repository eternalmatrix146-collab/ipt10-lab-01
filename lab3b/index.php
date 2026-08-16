<?php
session_start();

$uploadDir = __DIR__ . '/uploads/';
$relativePath = 'uploads/';

$messages = [];
$uploadedFiles = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['uploaded_files']) || empty($_FILES['uploaded_files']['name'][0])) {
        $messages[] = ['type' => 'warning', 'text' => 'Please select at least one file to upload.'];
    } else {
        $files = $_FILES['uploaded_files'];
        $fileCount = count($files['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = basename($files['name'][$i]);
            $fileTmp = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileError = $files['error'][$i];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedTypes = [
                'pdf' => ['application/pdf'],
                'audio' => ['audio/mpeg', 'audio/mp3'],
                'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
                'video' => ['video/mp4']
            ];
            
            $allowedExtensions = ['pdf', 'mp3', 'jpeg', 'jpg', 'png', 'gif', 'webp', 'svg', 'mp4'];
            $maxFileSize = 50 * 1024 * 1024;
            
            if ($fileError !== UPLOAD_ERR_OK) {
                $messages[] = ['type' => 'danger', 'text' => "Error uploading '$fileName'. Error code: $fileError"];
                continue;
            }
            
            if (!in_array($fileExt, $allowedExtensions)) {
                $messages[] = ['type' => 'danger', 'text' => "Invalid file type for '$fileName'. Allowed: PDF, MP3, Images, MP4."];
                continue;
            }
            
            if ($fileSize > $maxFileSize) {
                $messages[] = ['type' => 'danger', 'text' => "File '$fileName' exceeds 50MB limit."];
                continue;
            }
            
            $newFileName = uniqid() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $fileName);
            $destination = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmp, $destination)) {
                $mimeType = mime_content_type($destination);
                $fileCategory = 'unknown';
                
                if (in_array($mimeType, $allowedTypes['pdf'])) $fileCategory = 'pdf';
                elseif (in_array($mimeType, $allowedTypes['audio'])) $fileCategory = 'audio';
                elseif (in_array($mimeType, $allowedTypes['image'])) $fileCategory = 'image';
                elseif (in_array($mimeType, $allowedTypes['video'])) $fileCategory = 'video';
                
                $uploadedFiles[] = [
                    'name' => $fileName,
                    'path' => $relativePath . $newFileName,
                    'category' => $fileCategory,
                    'size' => $fileSize,
                    'mime' => $mimeType
                ];
                $messages[] = ['type' => 'success', 'text' => "Successfully uploaded '$fileName'!"];
            } else {
                $messages[] = ['type' => 'danger', 'text' => "Failed to upload '$fileName'."];
            }
        }
    }
    
    $_SESSION['messages'] = $messages;
    $_SESSION['uploaded_files'] = $uploadedFiles;
    header('Location: uploaded.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FileVault - Upload Center</title>
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.35);
        }
        .icon-circle i { color: white; font-size: 32px; }
        .title { color: #111827; font-weight: 800; letter-spacing: -0.02em; }
        .subtitle { color: #6b7280; font-weight: 500; }
        .upload-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 2px dashed #e5e7eb;
            transition: all 0.3s ease;
            text-align: center;
        }
        .upload-card:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
        }
        .upload-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            font-size: 24px;
        }
        .upload-icon.pdf { background: #fef2f2; color: #dc2626; }
        .upload-icon.audio { background: #f0fdf4; color: #16a34a; }
        .upload-icon.image { background: #eff6ff; color: #2563eb; }
        .upload-icon.video { background: #fefce8; color: #ca8a04; }
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-input-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            border: 2px solid #e5e7eb;
            background: white;
            color: #374151;
            transition: all 0.2s ease;
            width: 100%;
        }
        .file-input-wrapper:hover .file-input-btn {
            border-color: var(--primary);
            color: var(--primary);
        }
        .file-name-display {
            margin-top: 8px;
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
            min-height: 20px;
        }
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
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-panel">
            <div class="panel-header">
                <div class="icon-circle">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h1 class="title is-2">FileVault Upload Center</h1>
                <p class="subtitle is-5">Upload PDFs, audio, images, and videos in one place</p>
            </div>

            <div class="panel-body">
                <form method="POST" action="" enctype="multipart/form-data" novalidate>
                    <div class="columns is-multiline">
                        <div class="column is-6">
                            <div class="upload-card">
                                <div class="upload-icon pdf">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <h3 class="title is-5 mb-3">PDF Documents</h3>
                                <p class="is-size-7 has-text-grey mb-4">Upload PDF files only</p>
                                <div class="file-input-wrapper">
                                    <input type="file" name="uploaded_files[]" accept=".pdf,application/pdf" onchange="updateFileName(this, 'pdf-name')" />
                                    <div class="file-input-btn">
                                        <i class="fas fa-folder-open"></i>
                                        <span>Choose PDF File</span>
                                    </div>
                                </div>
                                <div id="pdf-name" class="file-name-display"></div>
                            </div>
                        </div>

                        <div class="column is-6">
                            <div class="upload-card">
                                <div class="upload-icon audio">
                                    <i class="fas fa-music"></i>
                                </div>
                                <h3 class="title is-5 mb-3">Audio Files</h3>
                                <p class="is-size-7 has-text-grey mb-4">Upload MP3 audio files</p>
                                <div class="file-input-wrapper">
                                    <input type="file" name="uploaded_files[]" accept=".mp3,audio/mpeg,audio/mp3" onchange="updateFileName(this, 'audio-name')" />
                                    <div class="file-input-btn">
                                        <i class="fas fa-folder-open"></i>
                                        <span>Choose MP3 File</span>
                                    </div>
                                </div>
                                <div id="audio-name" class="file-name-display"></div>
                            </div>
                        </div>

                        <div class="column is-6">
                            <div class="upload-card">
                                <div class="upload-icon image">
                                    <i class="fas fa-image"></i>
                                </div>
                                <h3 class="title is-5 mb-3">Image Files</h3>
                                <p class="is-size-7 has-text-grey mb-4">Upload JPG, PNG, GIF, WebP, SVG</p>
                                <div class="file-input-wrapper">
                                    <input type="file" name="uploaded_files[]" accept="image/*" onchange="updateFileName(this, 'image-name')" />
                                    <div class="file-input-btn">
                                        <i class="fas fa-folder-open"></i>
                                        <span>Choose Image File</span>
                                    </div>
                                </div>
                                <div id="image-name" class="file-name-display"></div>
                            </div>
                        </div>

                        <div class="column is-6">
                            <div class="upload-card">
                                <div class="upload-icon video">
                                    <i class="fas fa-video"></i>
                                </div>
                                <h3 class="title is-5 mb-3">Video Files</h3>
                                <p class="is-size-7 has-text-grey mb-4">Upload MP4 video files</p>
                                <div class="file-input-wrapper">
                                    <input type="file" name="uploaded_files[]" accept=".mp4,video/mp4" onchange="updateFileName(this, 'video-name')" />
                                    <div class="file-input-btn">
                                        <i class="fas fa-folder-open"></i>
                                        <span>Choose MP4 File</span>
                                    </div>
                                </div>
                                <div id="video-name" class="file-name-display"></div>
                            </div>
                        </div>
                    </div>

                    <div class="has-text-centered mt-6">
                        <button type="submit" class="button is-link is-medium">
                            <i class="fas fa-upload"></i>
                            <span>Upload All Files</span>
                        </button>
                        <a href="uploaded.php" class="button is-medium ml-3" style="background: #f3f4f6; color: #374151;">
                            <i class="fas fa-eye"></i>
                            <span>View Uploaded Files</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateFileName(input, displayId) {
            const display = document.getElementById(displayId);
            if (display) {
                if (input.files && input.files.length > 0) {
                    display.textContent = 'Selected: ' + input.files[0].name;
                    display.style.color = '#111827';
                } else {
                    display.textContent = '';
                }
            }
        }
    </script>
</body>
</html>
