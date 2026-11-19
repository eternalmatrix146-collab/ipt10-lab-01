<?php

define('REGISTRATIONS_FILE_PATH', 'registrations.csv');

// Column order written by thank-you.php:
// Complete Name, Birthday, Age, Contact Number, Sex, Program, Complete Address, Email Address
$COLUMN_LABELS = [
    'Complete Name',
    'Birthday',
    'Age',
    'Contact Number',
    'Sex',
    'Program',
    'Complete Address',
    'Email Address',
];

function get_all_registrants_data()
{
    $data = [];

    // The file may not exist yet if no one has registered.
    if (!file_exists(REGISTRATIONS_FILE_PATH)) {
        return $data;
    }

    $opened_file_handler = fopen(REGISTRATIONS_FILE_PATH, 'r');

    while (!feof($opened_file_handler)) {

        $row = fgetcsv($opened_file_handler, 1024);

        if (!empty($row)) {
            array_push($data, $row);
        }

    }

    fclose($opened_file_handler);

    return $data;
}

$registrants = get_all_registrants_data();
$registrant_count = count($registrants);

?>
<html>
<head>
    <meta charset="utf-8">
    <title>IPT10 Laboratory Activity #2</title>
    <link rel="icon" href="https://phpsandbox.io/assets/img/brand/phpsandbox.png">
    <link rel="stylesheet" href="https://assets.ubuntu.com/v1/vanilla-framework-version-4.15.0.min.css" />
</head>
<body>

<h1>
    Registrants
</h1>
<small>
Data source: <code>registrations.csv</code>
</small>

<p>
Showing <strong><?php echo $registrant_count; ?></strong> registrant(s).
</p>

<table aria-label="Registrants Dataset">
    <thead>
        <tr>
        <?php foreach ($COLUMN_LABELS as $label): ?>
            <th><?php echo $label; ?></th>
        <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php if ($registrant_count === 0): ?>
        <tr>
            <td colspan="<?php echo count($COLUMN_LABELS); ?>">No registrants yet.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($registrants as $record): ?>
        <tr>
            <td><?php echo htmlspecialchars($record[0]); ?></td>
            <td><?php echo htmlspecialchars($record[1]); ?></td>
            <td><?php echo htmlspecialchars($record[2]); ?></td>
            <td><?php echo htmlspecialchars($record[3]); ?></td>
            <td><?php echo htmlspecialchars($record[4]); ?></td>
            <td><?php echo htmlspecialchars($record[5]); ?></td>
            <td><?php echo htmlspecialchars($record[6]); ?></td>
            <td><?php echo htmlspecialchars($record[7]); ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<p>
<a href="index.php">Back to home</a>
</p>

</body>
</html>
