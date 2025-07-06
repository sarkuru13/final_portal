<?php
session_start();
require '../config/db.php';

// Ensure only authorized admins can access this script
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized Access');
}

// Handle filtering
$filter_exam_id = $_GET['exam_id'] ?? 'all';
$query_conditions = "";
$params = [];

if ($filter_exam_id !== 'all' && is_numeric($filter_exam_id)) {
    $query_conditions = "WHERE r.exam_id = ?";
    $params[] = $filter_exam_id;
}

// Fetch results based on the filter, including student's name
$results_stmt = $pdo->prepare("
    SELECT u.name, u.email, e.title, r.score, e.total_marks, r.submitted_at
    FROM results r
    JOIN users u ON r.student_id = u.id
    JOIN exams e ON r.exam_id = e.id
    $query_conditions
    ORDER BY r.submitted_at DESC
");
$results_stmt->execute($params);
$results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel download
$filename = "exam_results_" . date('Y-m-d') . ".xls";
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '";');

// Define passing percentage
$passing_percentage = 40;

// Start HTML output for Excel
$output = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exam Results Report</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        font-size: 14px;
    }
    th, td {
        border: 1px solid #bdc3c7;
        text-align: left;
        padding: 10px;
        vertical-align: middle;
    }
    th {
        background-color: #34495e;
        color: white;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 12px;
    }
    tr:nth-child(even) {
        background-color: #f7f9f9;
    }
    .status-pass {
        background-color: #d4efdf !important;
        color: #1e8449;
        font-weight: bold;
    }
    .status-fail {
        background-color: #fadedb !important;
        color: #a93226;
        font-weight: bold;
    }
</style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th width="180">Student Name</th>
                <th width="220">Student Email</th>
                <th width="250">Exam Title</th>
                <th width="80">Score</th>
                <th width="100">Total Marks</th>
                <th width="100">Percentage</th>
                <th width="80">Status</th>
                <th width="160">Submitted At</th>
            </tr>
        </thead>
        <tbody>
HTML;

// Write the data rows to the HTML table
if (!empty($results)) {
    foreach ($results as $row) {
        $percentage = ($row['total_marks'] > 0) ? round(($row['score'] / $row['total_marks']) * 100, 2) : 0;
        $status = ($percentage >= $passing_percentage) ? 'Pass' : 'Fail';
        $status_class = ($status === 'Pass') ? 'status-pass' : 'status-fail';

        $output .= "<tr>";
        $output .= "<td>" . htmlspecialchars(strtoupper($row['name'])) . "</td>";
        $output .= "<td>" . htmlspecialchars($row['email']) . "</td>";
        $output .= "<td>" . htmlspecialchars($row['title']) . "</td>";
        $output .= "<td style='mso-number-format:\"0\"'>" . $row['score'] . "</td>";
        $output .= "<td style='mso-number-format:\"0\"'>" . $row['total_marks'] . "</td>";
        $output .= "<td style='mso-number-format:\"0.00\"'>" . $percentage . "</td>";
        $output .= "<td class='" . $status_class . "'>" . $status . "</td>";
        $output .= "<td>" . date('Y-m-d H:i:s', strtotime($row['submitted_at'])) . "</td>";
        $output .= "</tr>";
    }
} else {
    $output .= "<tr><td colspan='8' style='text-align:center; padding: 20px;'>No results found for the selected filter.</td></tr>";
}

// End HTML output
$output .= <<<HTML
    </tbody>
</table>
</body>
</html>
HTML;

echo $output;
exit();
?>
