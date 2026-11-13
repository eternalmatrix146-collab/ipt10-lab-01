<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$complete_name = $_POST['complete_name'];
$email = $_POST['email'];
$birthdate = $_POST['birthdate'];
$contact_number = $_POST['contact_number'];

$name_parts = explode(' ', trim($complete_name));
$first_name = $name_parts[0];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz App - Instructions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --surface: rgba(255, 255, 255, 0.96);
        }
        * {
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
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
        .panel-body {
            padding: 2rem;
        }
        .icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.35);
        }
        .icon-circle i {
            color: white;
            font-size: 28px;
        }
        .title {
            color: #111827;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .subtitle {
            color: #6b7280;
            font-weight: 500;
        }
        .greeting-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
        }
        .instruction-box {
            background: #f9fafb;
            border-left: 4px solid var(--primary);
            border-radius: 0 12px 12px 0;
            padding: 1.25rem 1.5rem;
            line-height: 1.8;
            color: #4b5563;
            font-size: 15px;
            margin-bottom: 1.5rem;
        }
        .terms-box {
            background: #fffbeb;
            border: 2px solid #fde68a;
            border-radius: 12px;
            padding: 1.25rem;
            max-height: 220px;
            overflow-y: auto;
            font-size: 14px;
            line-height: 1.8;
            color: #92400e;
        }
        .field label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.5rem;
        }
        .field label i {
            color: var(--primary);
            font-size: 14px;
        }
        .textarea {
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            transition: all 0.2s ease;
            background: #f9fafb;
            resize: vertical;
        }
        .checkbox {
            font-size: 15px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        .checkbox a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        .checkbox a:hover {
            text-decoration: underline;
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
            cursor: pointer;
        }
        .button.is-link {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
        }
        .button.is-link:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        .button:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none;
        }
        .container {
            max-width: 680px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-panel">
            <div class="panel-header">
                <div class="icon-circle">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h1 class="title is-2">Quiz Instructions</h1>
                <p class="subtitle is-5">Read carefully before starting</p>
            </div>

            <div class="panel-body">
                <form method="POST" action="quiz.php">
                    <input type="hidden" name="complete_name" value="<?php echo htmlspecialchars($complete_name); ?>" />
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>" />
                    <input type="hidden" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>" />
                    <input type="hidden" name="contact_number" value="<?php echo htmlspecialchars($contact_number); ?>" />
                    <input type="hidden" name="agree" value="1" />

                    <div class="has-text-centered mb-5">
                        <div class="greeting-badge">
                            <i class="fas fa-hand-sparkles"></i>
                            <span>Hello <strong><?php echo htmlspecialchars($first_name); ?></strong>, please read the instructions first</span>
                        </div>
                    </div>

                    <div class="instruction-box">
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                    </div>

                    <div class="field">
                        <label>
                            <i class="fas fa-file-contract"></i> Terms and Conditions
                        </label>
                        <div class="control">
                            <div class="terms-box">
                                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <div class="control">
                            <label class="checkbox">
                                <input type="checkbox" id="agree_checkbox" name="agree" value="1" onchange="toggleStartBtn()">
                                <span>I agree to the <a href="#">terms and conditions</a></span>
                            </label>
                        </div>
                    </div>

                    <div class="field mt-6">
                        <div class="control">
                            <button id="start_btn" type="submit" class="button is-link is-fullwidth" disabled>
                                <span>Start Quiz</span>
                                <span class="icon"><i class="fas fa-play"></i></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleStartBtn() {
            const checkbox = document.getElementById('agree_checkbox');
            const btn = document.getElementById('start_btn');
            btn.disabled = !checkbox.checked;
        }
    </script>
</body>
</html>
