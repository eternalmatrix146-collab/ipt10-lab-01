# FileFlow Report Exporter

A PHP CLI application that demonstrates the Singleton and Factory Method design patterns for exporting reports in multiple file formats (TXT and JSON). It uses Composer with PSR-4 autoloading to manage class dependencies, mapping the `App\` namespace to the `src/` directory.

## Requirements

- PHP CLI (8.0+ recommended)
- Composer

## Setup

```bash
composer install
composer dump-autoload
```

## Run

```bash
php app.php txt
php app.php json
```

The first command generates `output/daily_report.txt` and the second generates `output/daily_report.json`.

## Developers

Student 1: [Your Name]
Student 2: [Partner Name]
