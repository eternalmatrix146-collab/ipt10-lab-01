<?php
require "helpers.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$complete_name = $_POST['complete_name'];
$email = $_POST['email'];
$birthdate_raw = $_POST['birthdate'];
$contact_number = $_POST['contact_number'];

$birthdate_obj = DateTime::createFromFormat('Y-m-d', $birthdate_raw);
$birthdate = $birthdate_obj ? $birthdate_obj->format('F d, Y') : $birthdate_raw;

$answers = isset($_POST['answer']) ? $_POST['answer'] : [];
$score = compute_score($answers);

$questions = retrieve_questions();
$correct_answers = $questions['answers'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz App - Results</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <?php if ($score == MAX_QUESTION_NUMBER): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/confetti-js@0.0.18/site/site.min.css">
    <script src="https://cdn.jsdelivr.net/npm/confetti-js@0.0.18/dist/index.min.js"></script>
    <?php endif; ?>
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
            padding: 0;
        }
        .hero.is-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .hero.is-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .hero.is-success .hero-body,
        .hero.is-danger .hero-body {
            padding: 3rem 1.5rem;
            text-align: center;
        }
        .hero.is-success .title,
        .hero.is-danger .title {
            color: white;
            font-size: clamp(1.75rem, 4vw, 3rem);
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .hero.is-success .subtitle,
        .hero.is-danger .subtitle {
            color: rgba(255,255,255,0.92);
            font-size: clamp(1rem, 2vw, 1.25rem);
        }
        .glass-panel {
            background: var(--surface);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: -30px;
            position: relative;
            z-index: 10;
            overflow: hidden;
        }
        .panel-header {
            text-align: center;
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .panel-body {
            padding: 1.5rem 2rem 2rem;
        }
        .icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .icon-circle.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .icon-circle.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .info-table td:first-child {
            width: 28%;
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
        }
        .info-table td:last-child {
            color: #111827;
        }
        .info-table i {
            color: var(--primary);
            width: 20px;
            text-align: center;
            margin-right: 8px;
        }
        .table-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
        }
        .table {
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 0;
        }
        .table thead {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        .table thead th {
            border-color: rgba(255,255,255,0.15);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.06em;
            padding: 12px 16px;
        }
        .table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #f9fafb;
        }
        .tag {
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            padding: 4px 10px;
        }
        .confetti-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
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
            max-width: 900px;
        }
        @media (max-width: 768px) {
            body {
                padding: 0;
            }
            .glass-panel {
                border-radius: 0;
                margin-top: 0;
            }
            .panel-header {
                padding: 1.5rem 1.25rem 1rem;
            }
            .panel-body {
                padding: 1.25rem;
            }
            .info-table td:first-child {
                width: 40%;
            }
        }
    </style>
</head>
<body>
    <?php if ($score > 2): ?>
    <section class="hero is-success">
        <div class="hero-body">
            <div class="icon-circle success">
                <i class="fas fa-trophy"></i>
            </div>
            <p class="title">Congratulations!</p>
            <p class="subtitle">You passed the quiz with flying colors</p>
        </div>
    </section>
    <?php else: ?>
    <section class="hero is-danger">
        <div class="hero-body">
            <div class="icon-circle danger">
                <i class="fas fa-heart-crack"></i>
            </div>
            <p class="title">Keep Trying!</p>
            <p class="subtitle">Better luck next time</p>
        </div>
    </section>
    <?php endif; ?>

    <div class="container">
        <div class="glass-panel">
            <div class="panel-header">
                <h2 class="title is-4">
                    <span class="icon"><i class="fas fa-user-circle"></i></span>
                    <span>Examinee Information</span>
                </h2>
            </div>

            <div class="panel-body">
                <div class="table-container">
                    <table class="table is-bordered is-hoverable is-fullwidth info-table">
                        <tbody>
                            <tr>
                                <td><i class="fas fa-user"></i>Complete Name</td>
                                <td><?php echo htmlspecialchars($complete_name); ?></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-envelope"></i>Email</td>
                                <td><?php echo htmlspecialchars($email); ?></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-calendar"></i>Birthdate</td>
                                <td><?php echo htmlspecialchars($birthdate); ?></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-phone"></i>Contact Number</td>
                                <td><?php echo htmlspecialchars($contact_number); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if ($score == MAX_QUESTION_NUMBER): ?>
                <div class="confetti-container" id="confetti-canvas"></div>
                <script>
                var confettiSettings = {
                    target: 'confetti-canvas'
                };
                var confetti = new ConfettiGenerator(confettiSettings);
                confetti.render();
                </script>
                <?php endif; ?>

                <h2 class="title is-4 mt-6 mb-4">
                    <span class="icon"><i class="fas fa-clipboard-check"></i></span>
                    <span>Question Review</span>
                </h2>
                <div class="table-container">
                    <table class="table is-bordered is-hoverable is-fullwidth">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Question</th>
                                <th>Correct Answer</th>
                                <th>Your Answer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions['questions'] as $index => $q): 
                                $user_answer = isset($answers[$index]) ? $answers[$index] : 'No answer';
                                $correct_answer = $correct_answers[$index];
                                $is_correct = $user_answer === $correct_answer;
                            ?>
                            <tr>
                                <td><?php echo ($index + 1); ?></td>
                                <td><?php echo htmlspecialchars($q['question']); ?></td>
                                <td>
                                    <span class="tag is-success"><?php echo htmlspecialchars($correct_answer); ?></span>
                                </td>
                                <td>
                                    <?php if ($user_answer !== 'No answer'): ?>
                                        <?php if ($is_correct): ?>
                                            <span class="tag is-success"><?php echo htmlspecialchars($user_answer); ?></span>
                                        <?php else: ?>
                                            <span class="tag is-danger"><?php echo htmlspecialchars($user_answer); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="tag is-light">No answer</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="has-text-centered mt-6">
                    <a href="index.php" class="button is-link">
                        <span>Take Quiz Again</span>
                        <span class="icon"><i class="fas fa-redo"></i></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
