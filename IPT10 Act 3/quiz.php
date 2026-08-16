<?php
require "helpers.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$complete_name = $_POST['complete_name'];
$email = $_POST['email'];
$birthdate = $_POST['birthdate'];
$contact_number = $_POST['contact_number'];
$agree = $_POST['agree'];

$questions = retrieve_questions();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz App - Questions</title>
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
            padding: 2rem 2rem 1.25rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .panel-body {
            padding: 1.5rem 2rem 2rem;
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.35);
        }
        .icon-circle i {
            color: white;
            font-size: 24px;
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
        .timer-badge {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .progress-track {
            background: #e5e7eb;
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .progress-fill {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            height: 100%;
            border-radius: 5px;
            width: 100%;
            transition: width 0.4s ease;
        }
        .question-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
            transition: all 0.25s ease;
            margin-bottom: 1.25rem;
            overflow: hidden;
        }
        .question-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
        }
        .question-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 16px 20px;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .question-body {
            padding: 20px;
        }
        .option-label {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            background: white;
            position: relative;
        }
        .option-label:hover {
            border-color: var(--primary);
            background: #f9fafb;
            transform: translateX(4px);
        }
        .option-label input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .option-key {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 50%;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .option-label:hover .option-key {
            transform: scale(1.1);
        }
        .option-text {
            font-size: 15px;
            color: #374151;
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
        .button.is-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        .container {
            max-width: 720px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-panel">
            <div class="panel-header">
                <div class="icon-circle">
                    <i class="fas fa-brain"></i>
                </div>
                <h1 class="title is-2">Quiz Time</h1>
                <p class="subtitle is-5">Answer all questions carefully</p>
            </div>

            <div class="panel-body">
                <div class="has-text-centered mb-4">
                    <span class="timer-badge">
                        <i class="fas fa-clock"></i>
                        <span id="timer">60</span> seconds remaining
                    </span>
                </div>

                <div class="progress-track">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>

                <form id="quiz_form" method="POST" action="result.php" novalidate>
                    <input type="hidden" name="complete_name" value="<?php echo htmlspecialchars($complete_name); ?>" />
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>" />
                    <input type="hidden" name="birthdate" value="<?php echo htmlspecialchars($birthdate); ?>" />
                    <input type="hidden" name="contact_number" value="<?php echo htmlspecialchars($contact_number); ?>" />
                    <input type="hidden" name="agree" value="<?php echo htmlspecialchars($agree); ?>" />

                    <?php foreach ($questions['questions'] as $index => $q): ?>
                    <div class="question-card">
                        <div class="question-header">
                            <span class="icon"><i class="fas fa-question-circle"></i></span>
                            <span>Question <?php echo ($index + 1); ?> of <?php echo count($questions['questions']); ?></span>
                        </div>
                        <div class="question-body">
                            <p class="title is-5 mb-4"><?php echo htmlspecialchars($q['question']); ?></p>
                            <?php foreach ($q['options'] as $option): ?>
                            <label class="option-label">
                                <input type="radio" name="answer[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($option['key']); ?>" required />
                                <span class="option-key"><?php echo htmlspecialchars($option['key']); ?></span>
                                <span class="option-text"><?php echo htmlspecialchars($option['value']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="has-text-centered mt-5">
                        <button type="submit" class="button is-link is-medium">
                            <span>Submit Quiz</span>
                            <span class="icon"><i class="fas fa-paper-plane"></i></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let timeLeft = 60;
        const totalTime = 60;
        function startTimer() {
            const timerElement = document.getElementById('timer');
            const progressFill = document.getElementById('progress-fill');
            const interval = setInterval(function() {
                timeLeft--;
                if (timerElement) {
                    timerElement.textContent = timeLeft;
                }
                if (progressFill) {
                    const pct = Math.max(0, (timeLeft / totalTime) * 100);
                    progressFill.style.width = pct + '%';
                }
                if (timeLeft <= 0) {
                    clearInterval(interval);
                    document.getElementById('quiz_form').submit();
                }
            }, 1000);
        }
        window.addEventListener('DOMContentLoaded', startTimer);
    </script>
</body>
</html>
