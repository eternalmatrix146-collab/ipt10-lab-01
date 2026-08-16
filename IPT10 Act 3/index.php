<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz App - Registration</title>
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
        .field {
            margin-bottom: 1.25rem;
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
        .control.has-icons-left .icon {
            color: #9ca3af;
            transition: color 0.2s ease;
        }
        .control.has-icons-left .input:focus + .icon,
        .control.has-icons-left .input:focus ~ .icon {
            color: var(--primary);
        }
        .input {
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.2s ease;
            background: #f9fafb;
            color: #111827;
        }
        .input::placeholder {
            color: #9ca3af;
            opacity: 1;
        }
        .input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: white;
            outline: none;
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
        .button:active:not(:disabled) {
            transform: translateY(0);
        }
        .button:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none;
        }
        .container {
            max-width: 520px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-panel">
            <div class="panel-header">
                <div class="icon-circle">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="title is-2">Create Account</h1>
                <p class="subtitle is-5">Join our quiz challenge and test your knowledge</p>
            </div>

            <div class="panel-body">
                <form method="POST" action="instructions.php" novalidate>
                    <div class="field">
                        <label for="complete_name">
                            <i class="fas fa-user"></i> Complete Name
                        </label>
                        <div class="control has-icons-left">
                            <input id="complete_name" class="input" type="text" name="complete_name" placeholder="Juan Dela Cruz" autocomplete="name" oninput="validateForm()">
                            <span class="icon is-small is-left"><i class="fas fa-user"></i></span>
                        </div>
                    </div>

                    <div class="field">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <div class="control has-icons-left">
                            <input id="email" class="input" name="email" type="email" placeholder="juan@example.com" autocomplete="email" oninput="validateForm()" />
                            <span class="icon is-small is-left"><i class="fas fa-envelope"></i></span>
                        </div>
                    </div>

                    <div class="field">
                        <label for="birthdate">
                            <i class="fas fa-calendar"></i> Birthdate
                        </label>
                        <div class="control">
                            <input class="input" name="birthdate" id="birthdate" type="date" onchange="updateBirthdateDisplay()" />
                        </div>
                        <p id="birthdate-display" class="help is-info mt-2" style="display:none; font-weight:600;"></p>
                    </div>

                    <div class="field">
                        <label for="contact_number">
                            <i class="fas fa-phone"></i> Contact Number
                        </label>
                        <div class="control has-icons-left">
                            <input class="input" name="contact_number" id="contact_number" type="tel" placeholder="09XXXXXXXXX" autocomplete="tel" />
                            <span class="icon is-small is-left"><i class="fas fa-phone"></i></span>
                        </div>
                    </div>

                    <div class="field mt-6">
                        <div class="control">
                            <button id="next_btn" type="submit" class="button is-link is-fullwidth" disabled>
                                <span>Proceed to Instructions</span>
                                <span class="icon"><i class="fas fa-arrow-right"></i></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateBirthdateDisplay() {
            const dateInput = document.getElementById('birthdate');
            const display = document.getElementById('birthdate-display');
            if (dateInput && display) {
                if (dateInput.value) {
                    const date = new Date(dateInput.value + 'T00:00:00');
                    const options = { year: 'numeric', month: 'long', day: 'numeric' };
                    display.textContent = 'Selected: ' + date.toLocaleDateString('en-US', options);
                    display.style.display = 'block';
                } else {
                    display.style.display = 'none';
                }
            }
        }

        function validateForm() {
            const name = document.getElementById('complete_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const isValid = name.length > 0 && emailRegex.test(email);
            document.getElementById('next_btn').disabled = !isValid;
        }
    </script>
</body>
</html>
