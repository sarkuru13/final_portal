<?php
require_once '../includes/admin_header.php';

// Fetch stats for the dashboard cards
$total_exams = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();
$total_questions = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_results = $pdo->query("SELECT COUNT(*) FROM results")->fetchColumn();

// Fetch data for the student performance chart
$results_stmt = $pdo->query("
    SELECT e.title, AVG(r.score / e.total_marks * 100) as avg_percentage
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    GROUP BY r.exam_id
    ORDER BY e.title
");
$chart_data = $results_stmt->fetchAll(PDO::FETCH_ASSOC);
$chart_json = json_encode($chart_data);

// Fetch recent exam results
$recent_results_stmt = $pdo->query("
    SELECT r.id, u.name, e.title, r.score, e.total_marks, r.submitted_at
    FROM results r
    JOIN users u ON r.student_id = u.id
    JOIN exams e ON r.exam_id = e.id
    ORDER BY r.submitted_at DESC
    LIMIT 5
");
$recent_results = $recent_results_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <div class="row g-3 my-2">
        <div class="col-md-3">
            <div class="p-3 bg-white shadow-sm d-flex justify-content-around align-items-center rounded stat-card">
                <div>
                    <h3 class="fs-2"><?php echo $total_exams; ?></h3>
                    <p class="fs-5">Exams</p>
                </div>
                <i class="fas fa-book-open fs-1 primary-text border rounded-full secondary-bg p-3"></i>
            </div>
        </div>

        <div class="col-md-3">
            <div class="p-3 bg-white shadow-sm d-flex justify-content-around align-items-center rounded stat-card">
                <div>
                    <h3 class="fs-2"><?php echo $total_questions; ?></h3>
                    <p class="fs-5">Questions</p>
                </div>
                <i class="fas fa-question-circle fs-1 primary-text border rounded-full secondary-bg p-3"></i>
            </div>
        </div>

        <div class="col-md-3">
            <div class="p-3 bg-white shadow-sm d-flex justify-content-around align-items-center rounded stat-card">
                <div>
                    <h3 class="fs-2"><?php echo $total_students; ?></h3>
                    <p class="fs-5">Students</p>
                </div>
                <i class="fas fa-user-graduate fs-1 primary-text border rounded-full secondary-bg p-3"></i>
            </div>
        </div>

        <div class="col-md-3">
            <div class="p-3 bg-white shadow-sm d-flex justify-content-around align-items-center rounded stat-card">
                <div>
                    <h3 class="fs-2"><?php echo $total_results; ?></h3>
                    <p class="fs-5">Submissions</p>
                </div>
                <i class="fas fa-poll fs-1 primary-text border rounded-full secondary-bg p-3"></i>
            </div>
        </div>
    </div>

    <div class="row my-5">
        <div class="col-md-7">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Average Exam Performance (%)</h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-5">
             <div class="card shadow-sm h-100">
                <div class="card-header">
                   <h5 class="mb-0">Recent Submissions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <tbody>
                                <?php if (empty($recent_results)): ?>
                                    <tr><td class="text-center text-muted">No recent submissions.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recent_results as $result): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($result['name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($result['title']); ?></small>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary rounded-pill fs-6"><?php echo $result['score'] . ' / ' . $result['total_marks']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
             </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartData = <?php echo $chart_json; ?>;
    if (chartData.length > 0) {
        const labels = chartData.map(d => d.title);
        const data = chartData.map(d => parseFloat(d.avg_percentage).toFixed(2));

        const ctx = document.getElementById('performanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Average Score (%)',
                    data: data,
                    backgroundColor: 'rgba(44, 62, 80, 0.7)',
                    borderColor: 'rgba(44, 62, 80, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                    hoverBackgroundColor: 'rgba(52, 73, 94, 0.9)'
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
                        } 
                    },
                    x: {
                        ticks: {
                            font: { family: "'Poppins', sans-serif" }
                        }
                    }
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#2c3e50',
                        titleFont: { family: "'Poppins', sans-serif", weight: 'bold' },
                        bodyFont: { family: "'Poppins', sans-serif" },
                    }
                }
            }
        });
    }

    const menuToggle = document.getElementById('menu-toggle');
    if(menuToggle) {
        menuToggle.addEventListener('click', function () {
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    }
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>
