<?php
require_once '../includes/functions.php';
checkUserType(['student']);

$db = new Database();
$conn = $db->getConnection();
$student_id = $_SESSION['user_id'];

$task_id = 0;
$assigned_task_id = 0;

if (isset($_GET['task_id']) && ctype_digit($_GET['task_id'])) {
    $task_id = (int)$_GET['task_id'];

    $stmt = $conn->prepare("
        SELECT at.id
        FROM assigned_tasks at
        WHERE at.student_id = ? AND at.task_id = ? AND at.is_completed = 0
        ORDER BY at.id DESC
        LIMIT 1
    ");
    $stmt->execute([$student_id, $task_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $assigned_task_id = (int)$existing['id'];
    } else {
        $stmt = $conn->prepare("
            SELECT created_by
            FROM tasks
            WHERE id = ?
        ");
        $stmt->execute([$task_id]);
        $task_owner = $stmt->fetch();

        if (!$task_owner) {
            $_SESSION['error'] = 'Задача не найдена';
            header('Location: tasks.php');
            exit();
        }

        $teacher_id = (int)$task_owner['created_by'];

        $stmt = $conn->prepare("
            INSERT INTO assigned_tasks (student_id, task_id, teacher_id, assigned_date)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$student_id, $task_id, $teacher_id]);

        $assigned_task_id = (int)$conn->lastInsertId();
    }
} elseif (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $assigned_task_id = (int)$_GET['id'];
} else {
    $_SESSION['error'] = 'Задача не выбрана';
    header('Location: tasks.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT
        at.id AS assigned_id,
        at.attempts,
        at.score,
        at.is_completed,
        at.completed_date,
        at.last_attempt,
        t.id AS task_id,
        t.title,
        t.task_type,
        t.difficulty,
        t.example_condition,
        t.example_solution,
        t.example_explanation,
        t.example_answer,
        t.task_condition,
        t.task_answer,
        t.correct_steps,
        t.hint1,
        t.hint2,
        u.full_name AS teacher_name
    FROM assigned_tasks at
    JOIN tasks t ON t.id = at.task_id
    LEFT JOIN users u ON u.id = t.created_by
    WHERE at.id = ? AND at.student_id = ?
");
$stmt->execute([$assigned_task_id, $student_id]);
$task = $stmt->fetch();

if (!$task) {
    $_SESSION['error'] = 'Задача не найдена';
    header('Location: tasks.php');
    exit();
}

$type_names = [
    'sblizhenie' => 'Сближение',
    'udalenie'   => 'Удаление',
    'dogonka'    => 'Вдогонку',
    'vstrecha'   => 'Встреча'
];

// correct_steps больше не используем
$attempts_used = (int)$task['attempts'];
$current_attempt = $attempts_used + 1;
$attempts_left = MAX_ATTEMPTS - $attempts_used;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostCsrf();

    $answer = trim($_POST['answer'] ?? '');
    $assigned_id = (int)($_POST['assigned_task_id'] ?? 0);

    if ($assigned_id !== (int)$task['assigned_id']) {
        $_SESSION['error'] = 'Ошибка запроса';
        header('Location: tasks.php');
        exit();
    }

    if ((int)$task['is_completed'] === 1) {
        $_SESSION['error'] = 'Задача уже завершена';
        header('Location: tasks.php');
        exit();
    }

    if ($answer === '') {
        $_SESSION['error'] = 'Введите ответ';
        header('Location: solve_task.php?id=' . $task['assigned_id']);
        exit();
    }

    $correct = trim((string)$task['task_answer']);
    $is_correct = trim($answer) === $correct;

    $stmt = $conn->prepare("
        INSERT INTO task_attempts (assigned_task_id, attempt_number, student_answer, is_correct)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $task['assigned_id'],
        $current_attempt,
        $answer,
        $is_correct ? 1 : 0
    ]);

    if ($is_correct) {
        $score = ($current_attempt == 1) ? 100 : max(60, 100 - (($current_attempt - 1) * 20));

        $stmt = $conn->prepare("
            UPDATE assigned_tasks
            SET attempts = attempts + 1,
                is_completed = 1,
                completed_date = NOW(),
                score = ?,
                last_attempt = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$score, $task['assigned_id']]);

        $_SESSION['success'] = 'Верно! Задача решена';
        header('Location: tasks.php');
        exit();
    } else {
        $new_attempts = $attempts_used + 1;

        if ($new_attempts >= MAX_ATTEMPTS) {
            $stmt = $conn->prepare("
                UPDATE assigned_tasks
                SET attempts = attempts + 1,
                    is_completed = 1,
                    completed_date = NOW(),
                    score = 0,
                    last_attempt = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$task['assigned_id']]);

            $_SESSION['error'] = 'Задача не решена';
            header('Location: tasks.php');
            exit();
        } else {
            $stmt = $conn->prepare("
                UPDATE assigned_tasks
                SET attempts = attempts + 1,
                    last_attempt = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$task['assigned_id']]);

            $step_hint = '';

            if ($new_attempts === 1 && !empty($task['hint1'])) {
                $step_hint = $task['hint1'];
            } elseif ($new_attempts === 2 && !empty($task['hint2'])) {
                $step_hint = $task['hint2'];
            }

            if ($step_hint !== '') {
                $_SESSION['error'] = 'Неверно. Подсказка: ' . $step_hint . '. Осталось попыток: ' . (MAX_ATTEMPTS - $new_attempts);
            } else {
                $_SESSION['error'] = 'Неверно. Осталось попыток: ' . (MAX_ATTEMPTS - $new_attempts);
            }

            header('Location: solve_task.php?id=' . $task['assigned_id']);
            exit();
        }
    }
}

$stmt = $conn->prepare("
    SELECT *
    FROM task_attempts
    WHERE assigned_task_id = ?
    ORDER BY attempt_number
");
$stmt->execute([$task['assigned_id']]);
$history = $stmt->fetchAll();

$page_title = 'Решение задачи';
$page_heading = 'Решение задачи';
$page_subtitle = $task['title'];

require_once '../includes/header.php';
?>

<div class="task-card">
    <h2><?php echo htmlspecialchars($task['title']); ?></h2>

    <p>
        <strong>Тип:</strong>
        <?php echo htmlspecialchars($type_names[$task['task_type']] ?? $task['task_type']); ?>
    </p>

    <p>
        <strong>Сложность:</strong>
        <?php echo getDifficultyBadge($task['difficulty']); ?>
    </p>

    <p>
        <strong>Автор:</strong>
        <?php echo htmlspecialchars($task['teacher_name'] ?? '—'); ?>
    </p>

    <p>
        <strong>Попытка:</strong>
        <?php echo $current_attempt; ?> из <?php echo MAX_ATTEMPTS; ?>
    </p>

    <hr style="margin: 20px 0;">

    <?php
    $has_example =
        trim((string)$task['example_condition']) !== '' ||
        trim((string)$task['example_solution']) !== '' ||
        trim((string)$task['example_explanation']) !== '' ||
        trim((string)$task['example_answer']) !== '';
    ?>

    <?php if ($has_example): ?>
        <h3>Пример</h3>
        <div class="task-card" style="margin-top: 10px;">
            <?php if (trim((string)$task['example_condition']) !== ''): ?>
                <p><?php echo nl2br(htmlspecialchars($task['example_condition'])); ?></p>
            <?php endif; ?>

            <?php if (trim((string)$task['example_solution']) !== ''): ?>
                <p style="margin-top: 15px;"><strong>Решение:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($task['example_solution'])); ?></p>
            <?php endif; ?>

            <?php if (trim((string)$task['example_explanation']) !== ''): ?>
                <p style="margin-top: 15px;">
                    <?php echo nl2br(htmlspecialchars($task['example_explanation'])); ?>
                </p>
            <?php endif; ?>

            <?php if (trim((string)$task['example_answer']) !== ''): ?>
                <p style="margin-top: 15px;">
                    <strong>Ответ:</strong> <?php echo htmlspecialchars($task['example_answer']); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <h3 style="margin-top: 25px;">Задача</h3>
    <div class="task-card" style="margin-top: 10px;">
        <p><?php echo nl2br(htmlspecialchars($task['task_condition'])); ?></p>
    </div>

    <?php if ((int)$task['is_completed'] === 0 && $attempts_used < MAX_ATTEMPTS): ?>
        <div class="form-container" style="margin-top: 20px;">
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="assigned_task_id" value="<?php echo (int)$task['assigned_id']; ?>">

                <div class="form-group">
                    <label>Введите ответ</label>
                    <input type="text" name="answer" required>
                </div>

                <button type="submit" class="btn btn-primary">Проверить</button>
            </form>
        </div>
    <?php else: ?>
        <div class="alert alert-success" style="margin-top: 20px;">
            Эта задача уже завершена.
        </div>
    <?php endif; ?>

    <div style="margin-top: 30px;">
        <h3>История попыток</h3>

        <?php if (empty($history)): ?>
            <div class="empty-state">
                <p>Попыток пока нет</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                    <tr>
                        <th>Попытка</th>
                        <th>Ответ</th>
                        <th>Результат</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?php echo (int)$h['attempt_number']; ?></td>
                            <td><?php echo htmlspecialchars($h['student_answer']); ?></td>
                            <td>
                                <?php if ($h['is_correct']): ?>
                                    <span class="badge badge-success">Верно</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Неверно</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px;">
        <a href="tasks.php" class="btn btn-secondary">← Назад к списку задач</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>