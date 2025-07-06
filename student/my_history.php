<?php
require_once '../includes/student_header.php';

// Fetch all results for the logged-in student
$stmt = $pdo->prepare("
    SELECT 
        e.title, 
        r.score, 
        e.total_marks,
        r.submitted_at
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.student_id = ?
    ORDER BY r.submitted_at DESC
");
$stmt->execute([$_SESSION['student_id']]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define the passing percentage
$passing_percentage = 40;
?>
<style>
    :root {
        --primary-color: #6a11cb;
        --secondary-color: #2575fc;
        --success-color: #27ae60;
        --danger-color: #c0392b;
        --light-gray-color: #f8f9fa;
        --table-header-bg: #343a40;
    }
    .page-header {
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 1rem;
    }
    .page-header h2 {
        font-weight: 600;
        color: var(--text-color);
    }
    .history-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    .table thead th {
        background-color: var(--table-header-bg);
        color: white;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border-bottom: 0;
    }
    .table tbody tr:hover {
        background-color: var(--light-gray-color);
    }
    .table td, .table th {
        vertical-align: middle;
    }
    .progress {
        height: 20px;
        border-radius: 10px;
        background-color: #e9ecef;
    }
    .progress-bar {
        border-radius: 10px;
        font-weight: 500;
        font-size: 0.8rem;
    }
    .status-badge {
        padding: 0.5em 1em;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        letter-spacing: 0.5px;
    }
    .status-badge.pass {
        background-color: var(--success-bg);
        color: var(--success-color);
    }
    .status-badge.fail {
        background-color: #fdedec;
        color: var(--danger-color);
    }
    .no-history-alert {
        padding: 3rem;
        background-color: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 10px;
    }
    .no-history-alert i {
        font-size: 3rem;
        color: #ced4da;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <h2 class="m-0">My Exam History</h2>
        <a href="dashboard.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
    </div>

    <div class="card history-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Exam Title</th>
                            <th>Your Score</th>
                            <th>Total Marks</th>
                            <th style="min-width: 150px;">Percentage</th>
                            <th class="text-center">Status</th>
                            <th class="pe-4">Date Attempted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="no-history-alert text-center my-3">
                                        <i class="fas fa-history mb-3"></i>
                                        <h4 class="text-muted">No History Found</h4>
                                        <p class="text-muted">You have not attempted any exams yet. Your results will appear here.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $index => $result): 
                                $percentage = ($result['total_marks'] > 0) ? round(($result['score'] / $result['total_marks']) * 100, 2) : 0;
                                $status = ($percentage >= $passing_percentage) ? 'Pass' : 'Fail';
                                $status_class = ($status === 'Pass') ? 'pass' : 'fail';
                                $progress_bar_class = ($status === 'Pass') ? 'bg-success' : 'bg-danger';
                            ?>
                                <tr>
                                    <td class="ps-4"><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($result['title']); ?></strong></td>
                                    <td><?php echo $result['score']; ?></td>
                                    <td><?php echo $result['total_marks']; ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar <?php echo $progress_bar_class; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $percentage; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="fas <?php echo ($status === 'Pass' ? 'fa-check-circle' : 'fa-times-circle'); ?> me-2"></i><?php echo $status; ?>
                                        </span>
                                    </td>
                                    <td class="pe-4"><?php echo date('d M Y, h:i A', strtotime($result['submitted_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/student_footer.php'; ?>
