# Decorator Pattern in PHP

## Description

This project demonstrates the Decorator design pattern with a notification system. An `EmailNotifier` provides the base email behavior. `LoggingDecorator` and `SmsDecorator` add responsibilities without changing the email notifier. The decorators can be combined because every class implements the same `Notifier` interface.

## Requirements

- PHP 8.1 or newer
- No Composer packages
- No external framework

## How to Run

Open a terminal in this folder and run:

```bash
php index.php
```

## Expected Output

```text
SMS sent to +63 912 345 6789: Your class starts at 8:00 AM.
[LOG] Notification requested: Your class starts at 8:00 AM.
Email sent to student@example.edu: Your class starts at 8:00 AM.
[LOG] Notification completed
```

## PHP Syntax-Check Command

```bash
php -l index.php
```

Expected result:

```text
No syntax errors detected in index.php
```
