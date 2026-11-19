# Training Enrollment System

A multi-table PHP + PDO web application for an organization that offers training
programs. Laboratory activity for **IPT — Integrative Programming and Technology**.

- **Name:** [Your Name]
- **Section:** [Your Section]

## Project description

One Administrator uses the system to:

- **Manage courses** — add, edit and delete courses.
- **Manage classes** — each class belongs to a course and has a schedule, an instructor
  and a number of available slots.
- **Record a student** — saves the new student *and* enrolls them into a class in one step.
- **Enroll an existing student** into another class.
- **View enrollments** — joined with student, class and course details, with search,
  status filter and paging.
- **Cancel an enrollment** — the slot goes back to the class.
- **View a report** — active and cancelled enrollments per class.

Rules the system enforces:

- **No over-enrollment.** A class with 0 slots shows "No slots available."
- **No duplicate enrollment.** A student cannot have two active enrollments in the same class.
- **Data stays consistent.** Recording a student (insert student + insert enrollment +
  decrement slots) and cancelling (update status + restore slot) each run inside one
  **database transaction**. If any step fails, everything is rolled back.

All database work uses **PDO with prepared statements**.

## Requirements

- XAMPP (Apache + MySQL/MariaDB + PHP 7.4 or newer)
- A web browser

## Setup instructions

1. **Copy the project** into your XAMPP web folder so the path is
   `C:\xampp\htdocs\training_enrollment\`.
2. **Start Apache and MySQL** in the XAMPP Control Panel.
3. **Create the database.** Open <http://localhost/phpmyadmin>, click **Import**,
   choose `sql/schema.sql`, and click **Import** (or **Go**).
   This creates the `training_db` database with sample data.
   (`training_db.sql` is a full export and can be imported instead.)
4. **Create the config file.** Copy `config/db.sample.php` and name the copy
   `config/db.php`. The XAMPP defaults (user `root`, empty password) already work.
   Change them only if your MySQL uses a different user or password.
5. **Open the application:** <http://localhost/training_enrollment/>
6. **Log in** with:
   - Username: `admin`
   - Password: `admin123`

> `config/db.php` is listed in `.gitignore`, so real passwords are never pushed to
> GitHub. Only the sample file is in the repository.

> Running `sql/schema.sql` again deletes the four tables and re-creates them with the
> sample data. This is useful for starting over, but it erases what you added.

## Sample data

| Class | Course | Slots left |
|---|---|---|
| WEB101-A | Web Development Fundamentals | 5 |
| WEB101-B | Web Development Fundamentals | **0 (full)** |
| DB101-A | Database Design Basics | 3 |
| NET101-A | Networking Essentials | 10 |

Class **WEB101-B** is full on purpose. Record a student into it to see the
"No slots available" message and the rollback.

## Folder structure

```
training_enrollment/
|-- config/
|   |-- db.sample.php            # Sample settings (copy to db.php)
|   `-- db.php                   # Your settings - creates the single PDO connection (not on GitHub)
|-- classes/
|   |-- Database.php             # PDO wrapper (Singleton pattern)
|   |-- Pet.php                  # Unrelated PDO CRUD example (given)
|   |-- Student.php              # Student data access
|   |-- Course.php               # Course data access
|   |-- ClassSection.php         # Class data access
|   `-- EnrollmentRepository.php # Record student, enroll, cancel (transactions) + lists
|-- admin/
|   |-- courses.php              # Manage courses (CRUD)
|   |-- classes.php              # Manage class schedules and slots
|   |-- students.php             # Student recording form (multi-table)
|   |-- enroll.php               # Enroll an existing student (transactional)
|   |-- enrollments.php          # List enrollments (JOINs), search, filter, cancel
|   `-- reports.php              # Summary report per class
|-- includes/
|   |-- auth.php                 # Login check used by every page
|   |-- header.php               # Common header / navigation
|   `-- footer.php               # Common footer
|-- css/
|   `-- style.css                # Basic styling
|-- sql/
|   `-- schema.sql               # Database schema + sample data
|-- training_db.sql              # Export of the database
|-- index.php                    # Landing page
|-- login.php                    # Administrator log in
|-- logout.php                   # Log out
|-- DESIGN_PATTERNS.md           # Design-pattern explanation
`-- README.md
```

## Database

Four related tables:

- `courses` — one course has many classes.
- `classes` — belongs to a course (`course_id` foreign key). `slots` = slots still available.
- `students`
- `enrollments` — links a student to a class (`student_id` and `class_id` foreign keys).
  `status` is `active` or `cancelled`.

## Design patterns

Two design patterns are used. The full explanation is in
[DESIGN_PATTERNS.md](DESIGN_PATTERNS.md).

### Singleton — `Database`

`Database` extends `PDO` and has a private constructor. The only way to get it is
`Database::getInstance()`, which always returns the **same object**.

**Why:** every class shares one database connection. This is faster, and it is required
for transactions — a transaction only works when all its steps use the same connection.

### Repository — `EnrollmentRepository`

All SQL about enrollments lives in this one class. Pages call methods such as
`recordStudent()`, `enroll()`, `cancel()` and `allWithDetails()` instead of writing SQL.

**Why:** pages stay simple (form, validation, message), and the rule "these steps must
succeed or fail together" is written in one place only.

## Security notes

- Prepared statements for every query (no SQL built from user input).
- Server-side validation on every form.
- Output is escaped with `htmlspecialchars()` before it is shown.
- The admin password is stored as a hash (`password_hash`), not as plain text.
- Add, edit, delete and cancel use POST requests.
