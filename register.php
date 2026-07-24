<?php
/**
 * Registration Form - AUF Student Registration
 * 
 * This page provides a registration form for students to enter their
 * personal information. It includes client-side and server-side validation,
 * and displays validation errors inline.
 * 
 * @package AUF Registration
 * @version 1.0
 */

// Initialize variables and error messages
$name = $dob = $sex = $email = $address = $department = $program = $mobile = '';
$nameErr = $emailErr = '';
$submitted = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input helper function
    /**
     * Sanitizes input data by trimming whitespace, stripping slashes, and converting special chars
     * 
     * @param string $data The raw input data
     * @return string The sanitized data
     */
    function sanitizeInput(string $data): string {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    // Validate Name - required
    if (empty($_POST['name'] ?? '')) {
        $nameErr = 'Name is required';
    } else {
        $name = sanitizeInput($_POST['name']);
    }

    // Sanitize other fields (not required, but clean them)
    $dob = sanitizeInput($_POST['dob'] ?? '');
    $sex = sanitizeInput($_POST['sex'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    $program = sanitizeInput($_POST['program'] ?? '');
    $mobile = sanitizeInput($_POST['mobile'] ?? '');

    // Validate Email - required
    if (empty($_POST['email'] ?? '')) {
        $emailErr = 'Email is required';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $emailErr = 'Invalid email format';
    } else {
        $email = sanitizeInput($_POST['email']);
    }

    // If no errors, redirect to summary page with form data as session
    if (empty($nameErr) && empty($emailErr)) {
        session_start();
        $_SESSION['reg_data'] = [
            'name' => $name,
            'dob' => $dob,
            'sex' => $sex,
            'email' => $email,
            'address' => $address,
            'department' => $department,
            'program' => $program,
            'mobile' => $mobile,
        ];
        header('Location: summary.php');
        exit;
    } else {
        $submitted = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUF Student Registration Form</title>
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
            width: 200px;
            height: 200px;
        }

        .form-body {
            padding: 20px 30px 30px;
        }

        .form-row {
            margin-bottom: 18px;
        }

        .form-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #333;
            font-size: 0.9rem;
        }

        .form-row label .required {
            color: #e53935;
            margin-left: 2px;
        }

        .form-row input[type="text"],
        .form-row input[type="email"],
        .form-row input[type="date"],
        .form-row select,
        .form-row textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #dde;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .form-row input:focus,
        .form-row select:focus,
        .form-row textarea:focus {
            border-color: #5c6bc0;
            box-shadow: 0 0 0 3px rgba(92, 107, 192, 0.15);
        }

        .form-row input.error,
        .form-row select.error,
        .form-row textarea.error {
            border-color: #e53935;
            box-shadow: 0 0 0 3px rgba(229, 57, 53, 0.12);
        }

        .form-row textarea {
            resize: vertical;
            min-height: 80px;
        }

        .radio-group {
            display: flex;
            gap: 30px;
            padding-top: 4px;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 400;
            cursor: pointer;
        }

        .radio-group input[type="radio"] {
            accent-color: #5c6bc0;
            width: 16px;
            height: 16px;
        }

        .error-message {
            color: #e53935;
            font-size: 0.8rem;
            margin-top: 4px;
            font-weight: 500;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 11px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
        }

        .btn-submit {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            color: #ffffff;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #0d1652 0%, #283593 100%);
            box-shadow: 0 4px 15px rgba(26, 35, 126, 0.3);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .btn-reset {
            background: #f5f5f5;
            color: #555;
            border: 2px solid #ddd;
        }

        .btn-reset:hover {
            background: #eeeeee;
            border-color: #ccc;
        }

        .btn-reset:active {
            transform: scale(0.98);
        }

        .footer {
            text-align: center;
            padding: 15px;
            background: #f9f9fb;
            border-top: 1px solid #eee;
            font-size: 0.8rem;
            color: #888;
        }

        @media (max-width: 480px) {
            body {
                padding: 10px 5px;
            }

            .form-body {
                padding: 15px;
            }

            .header {
                padding: 18px 15px;
            }

            .radio-group {
                flex-direction: column;
                gap: 8px;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AUF Student Registration</h1>
            <p>Please fill in the required fields to register</p>
        </div>

        <div class="logo-section">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT51c9ERJ_SUQ08B4raoLJCUwtPBVNkElzC7z22Nu3ESQ&s=10" alt="AUF Logo" width="200" height="200">
        </div>

        <div class="form-body">
            <form method="POST" action="register.php" novalidate id="regForm">

                <!-- Name -->
                <div class="form-row">
                    <label for="name">Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?= $name ?>" placeholder="Enter your full name" maxlength="100">
                    <?php if (!empty($nameErr)): ?>
                        <span class="error-message"><?= $nameErr ?></span>
                    <?php endif; ?>
                </div>

                <!-- Date of Birth -->
                <div class="form-row">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?= $dob ?>">
                </div>

                <!-- Sex -->
                <div class="form-row">
                    <label>Sex</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="sex" value="Male" <?= $sex === 'Male' ? 'checked' : '' ?>> Male
                        </label>
                        <label>
                            <input type="radio" name="sex" value="Female" <?= $sex === 'Female' ? 'checked' : '' ?>> Female
                        </label>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-row">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?= $email ?>" placeholder="example@email.com">
                    <?php if (!empty($emailErr)): ?>
                        <span class="error-message"><?= $emailErr ?></span>
                    <?php endif; ?>
                </div>

                <!-- Address -->
                <div class="form-row">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" placeholder="Enter your home address" maxlength="255"><?= $address ?></textarea>
                </div>

                <!-- College Department -->
                <div class="form-row">
                    <label for="department">College Department</label>
                    <select id="department" name="department">
                        <option value="">-- Select Department --</option>
                        <option value="College of Business Administration" <?= $department === 'College of Business Administration' ? 'selected' : '' ?>>College of Business Administration</option>
                        <option value="College of Education" <?= $department === 'College of Education' ? 'selected' : '' ?>>College of Education</option>
                        <option value="College of Engineering" <?= $department === 'College of Engineering' ? 'selected' : '' ?>>College of Engineering</option>
                        <option value="College of Information and Communications Technology" <?= $department === 'College of Information and Communications Technology' ? 'selected' : '' ?>>College of ICT</option>
                        <option value="College of Arts and Sciences" <?= $department === 'College of Arts and Sciences' ? 'selected' : '' ?>>College of Arts and Sciences</option>
                        <option value="College of Nursing" <?= $department === 'College of Nursing' ? 'selected' : '' ?>>College of Nursing</option>
                        <option value="College of Law" <?= $department === 'College of Law' ? 'selected' : '' ?>>College of Law</option>
                         <option value="College of Computer Studies" <?= $department === 'College of Computer Studies' ? 'selected' : '' ?>>College of Computer Studies</option>
                    </select>
                </div>

                <!-- Program -->
                <div class="form-row">
                    <label for="program">Program</label>
                    <input type="text" id="program" name="program" value="<?= $program ?>" placeholder="Enter your program name" maxlength="100">
                </div>

                <!-- Mobile Number -->
                <div class="form-row">
                    <label for="mobile">Mobile Number</label>
                    <input type="text" id="mobile" name="mobile" value="<?= $mobile ?>" placeholder="Enter your mobile number" maxlength="15">
                </div>

                <!-- Buttons -->
                <div class="button-group">
                    <button type="submit" class="btn btn-submit">Submit</button>
                    <button type="reset" class="btn btn-reset">Reset</button>
                </div>

            </form>
        </div>

        <div class="footer">
            &copy; <?= date('Y') ?> AUF Registration System. All rights reserved.
        </div>
    </div>
</body>
</html>