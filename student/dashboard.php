<?php
require_once '../includes/student_header.php';

// The student's name is now available from student_header.php
// $student_name is already fetched and sanitized there.

// Fetch exams the student has already taken
$completed_exams_stmt = $pdo->prepare("SELECT exam_id FROM results WHERE student_id = ?");
$completed_exams_stmt->execute([$_SESSION['student_id']]);
$completed_exam_ids = $completed_exams_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Fetch available exams and count their questions
$stmt = $pdo->query("
    SELECT e.id, e.title, e.total_questions, e.total_marks, COUNT(q.id) as question_count
    FROM exams e
    LEFT JOIN questions q ON e.id = q.exam_id
    GROUP BY e.id
    ORDER BY e.title ASC
");
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch student's results for the chart
$results_stmt = $pdo->prepare("
    SELECT e.title, r.score, e.total_marks
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.student_id = ?
    ORDER BY r.submitted_at
");
$results_stmt->execute([$_SESSION['student_id']]);
$results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);
$results_json = json_encode($results);
?>

<style>
    :root {
        --primary-color: #6a11cb;
        --secondary-color: #2575fc;
        --card-bg: #ffffff;
        --text-color: #333;
        --text-muted-color: #6c757d;
        --success-color: #2ecc71;
        --success-bg: #eafaf1;
        --disabled-color: #95a5a6;
        --disabled-bg: #ecf0f1;
    }
    .welcome-banner {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 3rem 2rem;
        border-radius: 15px;
        margin-bottom: 2.5rem;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        text-align: center;
    }
    .welcome-banner h1 {
        font-weight: 700;
        font-size: 2.8rem;
    }
    .welcome-banner p {
        font-size: 1.2rem;
        opacity: 0.9;
    }
    .section-title {
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 1.5rem;
        position: relative;
        padding-bottom: 10px;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 4px;
        background: var(--secondary-color);
        border-radius: 2px;
    }
    .exam-card {
        background: var(--card-bg);
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease-in-out;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .exam-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.1);
    }
    .exam-card .card-body {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        padding: 1.5rem;
    }
    .exam-card .card-icon {
        font-size: 2.5rem;
        color: var(--secondary-color);
        margin-bottom: 1rem;
    }
    .exam-card .card-title {
        font-weight: 600;
        color: var(--text-color);
        font-size: 1.2rem;
    }
    .exam-card .card-text {
        color: var(--text-muted-color);
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }
    .exam-card .btn-start-exam {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        border: none;
        font-weight: 500;
        border-radius: 50px;
        padding: 10px 25px;
        transition: all 0.3s ease;
    }
    .exam-card .btn-start-exam:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(37, 117, 252, 0.4);
    }
    .exam-card .status-badge {
        padding: 10px 25px;
        border-radius: 50px;
        font-weight: 500;
    }
    .exam-card .status-badge.completed {
        background-color: var(--success-bg);
        color: var(--success-color);
    }
    .exam-card .status-badge.not-ready {
        background-color: var(--disabled-bg);
        color: var(--disabled-color);
    }
    .exam-disabled {
        opacity: 0.7;
        pointer-events: none;
    }
    .performance-card {
        background-color: var(--card-bg);
        border-radius: 10px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        padding: 1.5rem;
    }
</style>

<div class="container py-4">
    <div class="welcome-banner">
        <h1 class="display-5 fw-bold">Welcome back, <?php echo htmlspecialchars(explode(' ', $student_name)[0]); ?>!</h1>
        <p class="col-md-10 mx-auto fs-5">Your journey to excellence starts now. Select an exam to begin.</p>
    </div>

    <h2 class="section-title">Available Exams</h2>
    <div class="row g-4">
        <?php if (empty($exams)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">No exams are available at the moment. Please check back later.</div>
            </div>
        <?php else: ?>
            <?php foreach ($exams as $exam): 
                $is_ready = $exam['question_count'] > 0;
                $has_completed = in_array($exam['id'], $completed_exam_ids);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card exam-card">
                        <div class="card-body text-center">
                            <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                            <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                            <p class="card-text">
                                <?php echo $exam['total_questions']; ?> Questions | <?php echo $exam['total_marks']; ?> Marks
                            </p>
                            <div class="mt-auto">
                                <?php if ($has_completed): ?>
                                    <span class="status-badge completed"><i class="fas fa-check-circle me-2"></i>Attempted</span>
                                <?php elseif ($is_ready): ?>
                                    <a href="exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-start-exam">Start Exam</a>
                                <?php else: ?>
                                    <span class="status-badge not-ready">Not Available</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <hr class="my-5">

    <h2 class="section-title">Your Performance Overview</h2>
    <div class="performance-card">
        <?php if (empty($results)): ?>
            <p class="text-center text-muted p-5">You have not completed any exams yet. Your performance chart will appear here.</p>
        <?php else: ?>
            <canvas id="resultsChart" style="height: 300px; width: 100%;"></canvas>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const resultsData = <?php echo $results_json; ?>;
    if (resultsData.length > 0) {
        const labels = resultsData.map(r => r.title);
        const scores = resultsData.map(r => (r.score / r.total_marks * 100).toFixed(2));
        const ctx = document.getElementById('resultsChart').getContext('2d');
        
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(37, 117, 252, 0.5)');
        gradient.addColorStop(1, 'rgba(106, 17, 203, 0.1)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Score (%)',
                    data: scores,
                    borderColor: '#6a11cb',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#6a11cb',
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#6a11cb',
                    pointRadius: 5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        max: 100, 
                        ticks: { 
                            callback: (v) => v + '%',
                            font: { family: "'Poppins', sans-serif" }
                        },
                        grid: {
                            color: '#e9ecef'
                        }
                    },
                    x: {
                        ticks: {
                            font: { family: "'Poppins', sans-serif" }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#333',
                        titleFont: { family: "'Poppins', sans-serif", weight: 'bold' },
                        bodyFont: { family: "'Poppins', sans-serif" },
                        callbacks: {
                            label: function(context) {
                                return `Score: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once '../includes/student_footer.php'; ?>
