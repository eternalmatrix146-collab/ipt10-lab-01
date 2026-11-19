<?php
/**
 * Summary Page - AUF Student Registration
 *
 * Displays a summary of the registrant's submitted data in a table.
 * Validates that session registration data exists; otherwise redirects
 * back to the registration page.
 *
 * @package AUF Registration
 * @version 1.0
 */

session_start();

// If no registration data in session, redirect back to registration page
if (!isset($_SESSION['reg_data']) || !is_array($_SESSION['reg_data'])) {
    header('Location: register.php');
    exit;
}

$data = $_SESSION['reg_data'];

// Determine name color based on sex
$nameColor = '';
if (isset($data['sex'])) {
    $nameColor = $data['sex'] === 'Male' ? '#1565c0' : '#c62828';
}

// Helper to safely output data
/**
 * Escapes a string for safe HTML output
 *
 * @param string|null $value The value to escape
 * @return string The escaped string or empty string if null
 */
function escape(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Format label for display
/**
 * Converts a field key to a human-readable label
 *
 * @param string $key The field key
 * @return string The formatted label
 */
function formatLabel(string $key): string {
    $labels = [
        'name' => 'Full Name',
        'dob' => 'Date of Birth',
        'sex' => 'Sex',
        'email' => 'Email Address',
        'address' => 'Home Address',
        'department' => 'College Department',
        'program' => 'Program',
        'mobile' => 'Mobile Number',
    ];
    return $labels[$key] ?? ucfirst($key);
}

// Clear the session data after reading
unset($_SESSION['reg_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Summary - AUF</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 30px 15px;
        }

        .container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            max-width: 620px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: #ffffff;
            padding: 25px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 0.9rem;
            opacity: 0.85;
        }

        .logo-section {
            display: flex;
            justify-content: center;
            padding: 15px 30px 5px;
            background: #ffffff;
        }

        .logo-section img {
            width: 150px;
            height: 150px;
        }

        .summary-body {
            padding: 20px 30px 30px;
        }

        .welcome-banner {
            text-align: center;
            padding: 15px;
            margin-bottom: 20px;
            background: #f5f5fb;
            border-radius: 8px;
            border-left: 4px solid #5c6bc0;
        }

        .welcome-banner h2 {
            font-size: 1.2rem;
            color: #333;
        }

        .name-highlight {
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead th {
            background: #1a237e;
            color: #ffffff;
            padding: 12px 15px;
            text-align: left;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        thead th:first-child {
            border-radius: 8px 0 0 0;
        }

        thead th:last-child {
            border-radius: 0 8px 0 0;
        }

        tbody td {
            padding: 11px 15px;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
            color: #444;
        }

        tbody tr:nth-child(even) {
            background: #f9f9fd;
        }

        tbody tr:hover {
            background: #f0f0fa;
        }

        .label-col {
            font-weight: 600;
            color: #555;
            width: 40%;
        }

        .value-col {
            width: 60%;
        }

        .footer {
            text-align: center;
            padding: 15px;
            background: #f9f9fb;
            border-top: 1px solid #eee;
            font-size: 0.8rem;
            color: #888;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #5c6bc0;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            body {
                padding: 10px 5px;
            }

            .summary-body {
                padding: 15px;
            }

            .header {
                padding: 18px 15px;
            }

            table {
                font-size: 0.8rem;
            }

            thead th,
            tbody td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AUF Registration Summary</h1>
            <p>Review your registration details below</p>
        </div>

        <div class="logo-section">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT51c9ERJ_SUQ08B4raoLJCUwtPBVNkElzC7z22Nu3ESQ&s=10" alt="AUF Logo" width="80" height="80">
        </div>

        <div class="summary-body">
            <div class="welcome-banner">
                <h2>Welcome, <span class="name-highlight" style="color: <?= $nameColor ?>"><?= escape($data['name']) ?></span></h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="label-col"><?= formatLabel('name') ?></td>
                        <td class="value-col"><?= escape($data['name']) ?></td>
                    </tr>
                    <tr>
                        <td class="label-col"><?= formatLabel('dob') ?></td>
                        <td class="value-col"><?= escape($data['dob']) ?: '—' ?></td>
                    </tr>
                    <tr>
                        <td class="label-col"><?= formatLabel('sex') ?></td>
                        <td class="value-col"><?= escape($data['sex']) ?: '—' ?></td>
                    </tr>
                    <tr>
                        <td class="label-col"><?= formatLabel('email') ?></td>
                        <td class="value-col"><?= escape($data['email']) ?></td>
                    </tr>
                    <tr>
                        <td class="label-col"><?= formatLabel('address') ?></td>
                        <td class="value-col"><?= escape($data['address']) ?: '—' ?></td>
                    </tr>
                    <tr>
                        <td class="label-col"><?= formatLabel('department') ?></td>
                        <td class="value-col"><?= escape($data['department']) ?: '—' ?></td>
                    </tr>
                    <tr>
                        <td class="label-col"><?= formatLabel('program') ?></td>
                        <td class="value-col"><?= escape($data['program']) ?: '—' ?></td>
                    </tr>
                    <tr>
                        <td class="label-col"><?= formatLabel('mobile') ?></td>
                        <td class="value-col"><?= escape($data['mobile']) ?: '—' ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="back-link">
                <a href="register.php">&larr; Back to Registration</a>
            </div>
        </div>

        <div class="footer">
            &copy; <?= date('Y') ?> AUF Registration System. All rights reserved.
        </div>
    </div>
</body>
</html>