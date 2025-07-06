<?php
require_once '../includes/student_header.php';

if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    header('Location: dashboard.php');
    exit;
}
$exam_id = $_GET['exam_id'];
$student_id = $_SESSION['student_id'];

// Check if student has already submitted this exam
$result_stmt = $pdo->prepare("SELECT id FROM results WHERE student_id = ? AND exam_id = ?");
$result_stmt->execute([$student_id, $exam_id]);
if ($result_stmt->fetch()) {
    echo "<div class='alert alert-warning text-center'>You have already completed this exam. <a href='dashboard.php'>Go to Dashboard</a></div>";
    require_once '../includes/student_footer.php';
    exit;
}

// Fetch exam details
$exam_stmt = $pdo->prepare("SELECT title, total_questions FROM exams WHERE id = ?");
$exam_stmt->execute([$exam_id]);
$exam = $exam_stmt->fetch(PDO::FETCH_ASSOC);
if (!$exam) {
    echo "<div class='alert alert-danger'>Exam not found.</div>";
    require_once '../includes/student_footer.php';
    exit;
}
$exam_title = $exam['title'];

// Fetch exam questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($questions)) {
    echo "<div class='alert alert-warning'>This exam has no questions yet. Please try again later.</div>";
    require_once '../includes/student_footer.php';
    exit;
}

// Fetch any saved progress
$progress_stmt = $pdo->prepare("SELECT answers FROM exam_progress WHERE student_id = ? AND exam_id = ?");
$progress_stmt->execute([$student_id, $exam_id]);
$saved_answers = $progress_stmt->fetchColumn();
$saved_answers_decoded = $saved_answers ? json_decode($saved_answers, true) : [];
?>
<style>
    :root {
        --primary-color: #6a11cb;
        --secondary-color: #2575fc;
        --bg-color: #f0f2f5;
        --card-bg: #ffffff;
        --text-color: #333;
        --text-muted: #6c757d;
        --border-color: #dee2e6;
        --answered-color: #27ae60;
        --visited-color: #f39c12;
        --not-visited-color: #7f8c8d;
    }
    body {
        background-color: var(--bg-color);
    }
    .exam-container {
        max-width: 100%;
        padding: 0;
    }
    /* Instruction Screen Styling */
    #instructionScreen {
        max-width: 800px;
        margin: 5vh auto;
        animation: fadeIn 0.5s ease-in-out;
    }
    #instructionScreen .card-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    }
    #instructionScreen ul {
        list-style: none;
        padding-left: 0;
    }
    #instructionScreen ul li {
        padding: 10px 0 10px 35px;
        position: relative;
        font-size: 1.1rem;
    }
    #instructionScreen ul li i {
        position: absolute;
        left: 0;
        top: 14px;
        color: var(--secondary-color);
    }
    .palette-legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .palette-legend {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: inline-block;
    }
    .palette-legend.answered { background-color: var(--answered-color); }
    .palette-legend.visited { background-color: var(--visited-color); }
    .palette-legend.not-visited { background-color: white; border: 2px solid var(--not-visited-color); }

    /* Exam Interface Styling */
    #examInterface {
        width: 100%;
    }
    .exam-header {
        background: var(--card-bg);
        padding: 1rem 2rem;
        border-bottom: 1px solid var(--border-color);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        position: sticky;
        top: 56px; /* Adjust based on navbar height */
        z-index: 1020;
    }
    .exam-header h4 {
        font-weight: 600;
        color: var(--primary-color);
    }
    .question-area {
        padding: 2rem;
    }
    .question-card {
        background: var(--card-bg);
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .question-card .card-header {
        background: none;
        border-bottom: 1px solid var(--border-color);
        font-weight: 600;
    }
    .option-label {
        display: block;
        background: #f8f9fa;
        border: 2px solid #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }
    .option-label:hover {
        border-color: var(--secondary-color);
    }
    .form-check-input[type="radio"] {
        display: none;
    }
    .form-check-input[type="radio"]:checked + .option-label {
        background: #eafaf1;
        border-color: var(--answered-color);
        color: var(--answered-color);
        font-weight: 600;
    }
    .navigation-buttons {
        padding: 1rem 2rem;
    }
    .palette-container {
        padding: 2rem;
    }
    .palette-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    #question-palette {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
        gap: 10px;
    }
    .palette-btn {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
    }
    .palette-btn.not-visited {
        background-color: white;
        border: 2px solid var(--not-visited-color);
        color: var(--not-visited-color);
    }
    .palette-btn.visited {
        background-color: var(--visited-color);
        color: white;
        border-color: var(--visited-color);
    }
    .palette-btn.answered {
        background-color: var(--answered-color);
        color: white;
        border-color: var(--answered-color);
    }
</style>

<div class="container-fluid exam-container">
    <!-- Initial Instructions Screen -->
    <div id="instructionScreen" class="card shadow-lg">
        <div class="card-header text-center bg-primary text-white">
            <h2 class="my-2">Instructions for <?php echo htmlspecialchars($exam_title); ?></h2>
        </div>
        <div class="card-body p-4 p-md-5">
            <h4 class="card-title">Please read the following instructions carefully:</h4>
            <ul class="my-4">
                <li><i class="fas fa-list-ol"></i>This exam consists of <strong><?php echo count($questions); ?> questions</strong>.</li>
                <li><i class="fas fa-check-double"></i>Each question has only one correct answer.</li>
                <li><i class="fas fa-save"></i>Your progress will be saved automatically every 2 seconds.</li>
                <li><i class="fas fa-exclamation-triangle"></i>Do not switch tabs, reload, or try to copy/paste. These actions will be reported.</li>
            </ul>
            <hr>
            <h5>Palette Legend:</h5>
            <div class="d-flex justify-content-around my-3 flex-wrap">
                <div class="palette-legend-item m-2"><span class="palette-legend answered"></span> Answered</div>
                <div class="palette-legend-item m-2"><span class="palette-legend visited"></span> Visited</div>
                <div class="palette-legend-item m-2"><span class="palette-legend not-visited"></span> Not Visited</div>
            </div>
            <hr>
            <div class="text-center">
                <p class="fs-5">All the best for your exam!</p>
                <button class="btn btn-lg btn-success" id="startExamBtn"><i class="fas fa-play-circle me-2"></i>Start Exam</button>
            </div>
        </div>
    </div>

    <!-- Main Exam Interface (Initially hidden) -->
    <div class="d-none" id="examInterface">
        <div class="exam-header d-flex justify-content-between align-items-center">
            <h4 class="m-0"><?php echo htmlspecialchars($exam_title); ?></h4>
        </div>
        <div class="row g-0">
            <div class="col-md-8 question-area">
                <form id="examForm">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="card question-card mb-4 d-none" id="q-card-<?php echo $index; ?>">
                            <div class="card-header d-flex justify-content-between">
                                <span>Question <?php echo $index + 1; ?> of <?php echo count($questions); ?></span>
                                <span>Marks: <?php echo $question['marks']; ?></span>
                            </div>
                            <div class="card-body p-4">
                                <p class="card-text fs-5 mb-4"><?php echo htmlspecialchars($question['question']); ?></p>
                                <?php $options = json_decode($question['options'], true); ?>
                                <?php foreach ($options as $option_key => $option): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" 
                                               name="answers[<?php echo $question['id']; ?>]" 
                                               id="q-<?php echo $question['id']; ?>-opt-<?php echo $option_key; ?>" 
                                               value="<?php echo htmlspecialchars($option); ?>"
                                               <?php echo (isset($saved_answers_decoded[$question['id']]) && $saved_answers_decoded[$question['id']] === $option) ? 'checked' : ''; ?>
                                               onchange="updatePalette(<?php echo $index; ?>, true)">
                                        <label class="option-label" for="q-<?php echo $question['id']; ?>-opt-<?php echo $option_key; ?>">
                                            <?php echo htmlspecialchars($option); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
                 <div class="d-flex justify-content-between align-items-center mt-4 navigation-buttons">
                    <button type="button" class="btn btn-secondary btn-lg" id="prevBtn" onclick="navigateQuestion(-1)" disabled>
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <div>
                        <span id="saveStatus" class="text-muted fst-italic"></span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary btn-lg" id="nextBtn" onclick="navigateQuestion(1)">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="button" class="btn btn-success btn-lg" id="submitBtn" onclick="submitExam()" style="display: none;">
                            <i class="fas fa-check-circle"></i> Submit Exam
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-4 bg-light palette-container">
                <div class="position-sticky" style="top: 80px;">
                    <div class="card palette-card">
                         <div class="card-header">
                            <h5 class="mb-0 text-center">Question Palette</h5>
                        </div>
                        <div class="card-body" id="question-palette">
                            <?php foreach ($questions as $index => $question): ?>
                                <button type="button" class="btn palette-btn not-visited" id="palette-btn-<?php echo $index; ?>" onclick="showQuestion(<?php echo $index; ?>)">
                                    <?php echo $index + 1; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Proctoring Violation Modal -->
<div class="modal fade" id="violationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="violationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-warning border-3">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="violationModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Warning: Exam Violation</h5>
      </div>
      <div class="modal-body fs-5">
        <p id="violationMessage">You have switched tabs or windows. This action is not permitted and has been reported.</p>
        <p class="text-danger fw-bold">Further violations may result in disqualification.</p>
      </div>
       <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">I Understand</button>
      </div>
    </div>
  </div>
</div>

<!-- Exam Locked Modal -->
<div class="modal fade" id="examLockedModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="examLockedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger border-3">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="examLockedModalLabel"><i class="fas fa-lock me-2"></i> Exam Locked</h5>
      </div>
      <div class="modal-body">
        <p class="fs-5">You have exceeded the maximum number of violations.</p>
        <p>Your exam has been locked. To continue, please enter the password provided by the proctor.</p>
        <div class="form-group mt-3">
            <input type="password" id="unlockPassword" class="form-control" placeholder="Enter password to unlock">
            <div id="unlockError" class="text-danger mt-2 d-none">Incorrect password. Please try again.</div>
        </div>
      </div>
       <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="unlockBtn">Unlock Exam</button>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const examForm = document.getElementById('examForm');
        const questions = document.querySelectorAll('.question-card');
        const totalQuestions = questions.length;
        let currentQuestionIndex = 0;
        const violationModal = new bootstrap.Modal(document.getElementById('violationModal'));
        const examLockedModal = new bootstrap.Modal(document.getElementById('examLockedModal'));
        const violationMessageEl = document.getElementById('violationMessage');
        const startExamBtn = document.getElementById('startExamBtn');
        const saveStatusEl = document.getElementById('saveStatus');

        let violationCount = 0;
        const MAX_VIOLATIONS = 7;

        window.showQuestion = function(index) {
            if (index < 0 || index >= totalQuestions) return;
            questions.forEach(q => q.classList.add('d-none'));
            questions[index].classList.remove('d-none');
            currentQuestionIndex = index;
            const paletteBtn = document.getElementById(`palette-btn-${index}`);
            if (!paletteBtn.classList.contains('answered')) {
                paletteBtn.className = 'btn palette-btn visited';
            }
            updateNavButtons();
        }

        window.navigateQuestion = function(direction) {
            showQuestion(currentQuestionIndex + direction);
        }

        function updateNavButtons() {
            document.getElementById('prevBtn').disabled = (currentQuestionIndex === 0);
            document.getElementById('nextBtn').style.display = (currentQuestionIndex === totalQuestions - 1) ? 'none' : 'inline-block';
            document.getElementById('submitBtn').style.display = (currentQuestionIndex === totalQuestions - 1) ? 'inline-block' : 'none';
        }

        window.updatePalette = function(index, isAnswered) {
            const btn = document.getElementById(`palette-btn-${index}`);
            if (isAnswered) {
                btn.className = 'btn palette-btn answered';
            }
        }

        function saveProgress() {
            const formData = new FormData(examForm);
            saveStatusEl.textContent = 'Saving...';
            
            fetch('save_progress.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    saveStatusEl.textContent = `Saved`;
                } else {
                    saveStatusEl.textContent = 'Save failed.';
                }
            })
            .catch(err => {
                console.error('Save failed:', err);
                saveStatusEl.textContent = 'Connection error.';
            });
        }

        window.submitExam = function(isAutoSubmit = false) {
            if (!isAutoSubmit && !confirm('Are you sure you want to submit the exam?')) {
                return;
            }
            saveProgress(); // Final save
            const formData = new FormData(examForm);
            fetch('submit_exam.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(`Exam submitted successfully! Your score is: ${data.score}`);
                    window.location.href = 'dashboard.php';
                } else {
                    alert('An error occurred: ' + (data.message || 'Unknown error.'));
                }
            });
        }
        
        function reportViolation(type) {
            violationCount++;
            
            // Log every violation to the database
            const examId = document.querySelector('input[name="exam_id"]').value;
            fetch('../report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ exam_id: examId, violation_type: `${type} (Violation #${violationCount})` })
            });

            if (violationCount > MAX_VIOLATIONS) {
                examLockedModal.show();
            } else {
                violationMessageEl.textContent = `Violation Detected: ${type}. This is warning ${violationCount} of ${MAX_VIOLATIONS}.`;
                violationModal.show();
            }
        }

        document.getElementById('unlockBtn').addEventListener('click', function() {
            const passwordInput = document.getElementById('unlockPassword');
            const unlockError = document.getElementById('unlockError');
            if (passwordInput.value === 'cheated') {
                examLockedModal.hide();
                passwordInput.value = '';
                unlockError.classList.add('d-none');
            } else {
                unlockError.classList.remove('d-none');
                passwordInput.focus();
            }
        });

        function startExam() {
            document.getElementById('instructionScreen').remove();
            document.getElementById('examInterface').classList.remove('d-none');
            
            // Activate security features
            document.body.classList.add('exam-in-progress');
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') reportViolation('Tab/Window Switched');
            });
            document.addEventListener('contextmenu', e => e.preventDefault());

            // Prevent reload and back navigation
            window.addEventListener('beforeunload', function (e) {
                e.preventDefault();
                e.returnValue = 'Are you sure you want to leave? Your progress will be saved, but leaving the exam is not recommended.';
                return e.returnValue;
            });

            // Disable F5 and Ctrl+R
            document.addEventListener('keydown', function (e) {
                if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
                    e.preventDefault();
                    reportViolation('Reload Attempted');
                }
            });
            
            // Initial setup
            showQuestion(0);
            
            <?php foreach ($questions as $index => $question): ?>
                <?php if (isset($saved_answers_decoded[$question['id']])): ?>
                    updatePalette(<?php echo $index; ?>, true);
                <?php endif; ?>
            <?php endforeach; ?>
            
            setInterval(saveProgress, 2000); // Save every 2 seconds
        }

        if (startExamBtn) {
            startExamBtn.addEventListener('click', startExam);
        }
    });
</script>

<?php require_once '../includes/student_footer.php'; ?>
