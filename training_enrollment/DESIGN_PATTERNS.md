# Design Patterns Used

This project uses two design patterns: **Singleton** and **Repository**.

## 1. Singleton — `classes/Database.php`

**What it is.** A Singleton is a class that can only ever have **one object**.

**How it is done here.**

- The constructor of `Database` is `private`, so no page can write `new Database(...)`.
- The only way to get the object is `Database::getInstance(...)`.
- The first call creates the connection and keeps it in the static `$instance` variable.
  Every later call returns that same object.

```php
// config/db.php
$db = Database::getInstance($dsn, $user, $pass);
```

**Why I used it.**

1. **One connection per request.** Opening a database connection is slow. Every class
   (`Course`, `ClassSection`, `Student`, `EnrollmentRepository`) shares the same one.
2. **Transactions need it.** A transaction belongs to one connection. Recording a student
   changes three tables. If each step used a different connection, `rollBack()` could not
   undo all of them. One shared connection makes the three steps succeed or fail together.
3. **One place for settings.** Error mode and fetch mode are set once, in one file.

## 2. Repository — `classes/EnrollmentRepository.php`

**What it is.** A Repository is a class that holds **all the database code for one
topic**. Pages ask the repository to do something; they do not write SQL themselves.

**How it is done here.** `EnrollmentRepository` owns everything about enrollments:

| Method | What it does |
|---|---|
| `recordStudent()` | Inserts the student, inserts the enrollment, and takes one slot — in **one transaction** |
| `enroll()` | Enrolls an existing student and takes one slot — in one transaction |
| `cancel()` | Sets the status to `cancelled` and gives the slot back — in one transaction |
| `allWithDetails()` | Lists enrollments joined with student, class and course |
| `search()`, `countSearch()` | The same list with search, status filter and paging |
| `summaryByClass()` | The report: active and cancelled enrollments per class |

A page only needs one line:

```php
// admin/students.php
$student_id = $repo->recordStudent($full_name, $email, $phone, $class_id);
```

**Why I used it.**

1. **Pages stay simple.** Files in `admin/` only read the form, validate it, call one
   method, and show a message. No SQL is mixed with HTML.
2. **The transaction rule lives in one place.** "Insert student + insert enrollment +
   decrement slots must all succeed or all be undone" is written once. No page can
   forget a step or do them in the wrong order.
3. **Easy to change and test.** If a table changes, only the repository changes.
   The methods can also be tested without opening a browser.

## How the two patterns work together

```
admin/students.php  ->  EnrollmentRepository  ->  Database (single PDO connection)  ->  MySQL
   (form + message)        (SQL + transaction)        (Singleton)
```

The page calls the repository. The repository runs its SQL through the one `Database`
object. Because there is only one connection, `beginTransaction()`, `commit()` and
`rollBack()` cover every step.

## The transaction in `recordStudent()`

```
beginTransaction()
  1. Check the class still has slots   -> if not: "No slots available."
  2. INSERT INTO students
  3. INSERT INTO enrollments
  4. UPDATE classes SET slots = slots - 1
commit()

If anything fails -> rollBack()  (no student row, no enrollment row, slots unchanged)
```

`cancel()` works the same way with two steps: set the status to `cancelled`, then
`slots = slots + 1`.
