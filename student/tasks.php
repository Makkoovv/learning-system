<?php
require_once '../includes/functions.php';
checkUserType(['student']);

$db = new Database();
$conn = $db->getConnection();
$student_id = $_SESSION['user_id'];

$type_names = [
    'sblizhenie' => 'Навстречу',
    'udalenie'   => 'Удаление',
    'dogonka'    => 'Вдогонку',
    'vstrecha'   => 'Встреча в точке'
];

$allowed_types = ['sblizhenie', 'udalenie', 'dogonka', 'vstrecha'];
$allowed_difficulty = ['easy', 'medium', 'hard'];
$allowed_status = ['all', 'solved', 'unsolved'];

$filter_type = $_GET['type'] ?? '';
$filter_difficulty = $_GET['difficulty'] ?? '';
$filter_status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

if (!in_array($filter_type, array_merge([''], $allowed_types), true)) {
    $filter_type = '';
}

if (!in_array($filter_difficulty, array_merge([''], $allowed_difficulty), true)) {
    $filter_difficulty = '';
}

if (!in_array($filter_status, $allowed_status, true)) {
    $filter_status = 'all';
}

$sql = "
    SELECT 
        t.id,
        t.title,
        t.task_type,
        t.task_condition,
        t.difficulty,
        t.created_at,
        u.full_name AS teacher_name,

        latest.id AS assigned_id,
        latest.is_completed,
        latest.score,
        latest.attempts,
        latest.last_attempt

    FROM tasks t
    LEFT JOIN users u ON u.id = t.created_by

    LEFT JOIN assigned_tasks latest 
        ON latest.id = (
            SELECT at2.id
            FROM assigned_tasks at2
            WHERE at2.task_id = t.id AND at2.student_id = ?
            ORDER BY at2.id DESC
            LIMIT 1
        )

    WHERE 1=1
";

$params = [$student_id];

if ($filter_type !== '') {
    $sql .= " AND t.task_type = ? ";
    $params[] = $filter_type;
}

if ($filter_difficulty !== '') {
    $sql .= " AND t.difficulty = ? ";
    $params[] = $filter_difficulty;
}

if ($search !== '') {
    $sql .= " AND (t.title LIKE ? OR t.task_condition LIKE ?) ";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($filter_status === 'solved') {
    $sql .= " AND latest.is_completed = 1 ";
} elseif ($filter_status === 'unsolved') {
    $sql .= " AND (latest.id IS NULL OR latest.is_completed = 0) ";
}

$sql .= " ORDER BY t.created_at DESC ";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

$page_title = 'Список задач';
$page_heading = 'Общий список задач';
$page_subtitle = 'Фильтрация по типу, сложности и статусу';

require_once '../includes/header.php';
?>

<div class="section-block">
    <h2 style="margin-bottom: 20px;">Фильтр задач</h2>

    <form method="GET">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:15px;">
            <div class="form-group">
                <label>Поиск</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Название или текст задачи">
            </div>

            <div class="form-group">
                <label>Тип задачи</label>
                <select name="type">
                    <option value="">Все типы</option>
                    <option value="sblizhenie" <?php echo $filter_type === 'sblizhenie' ? 'selected' : ''; ?>>Навстречу</option>
                    <option value="udalenie" <?php echo $filter_type === 'udalenie' ? 'selected' : ''; ?>>Удаление</option>
                    <option value="dogonka" <?php echo $filter_type === 'dogonka' ? 'selected' : ''; ?>>Вдогонку</option>
                    <option value="vstrecha" <?php echo $filter_type === 'vstrecha' ? 'selected' : ''; ?>>Встреча в точке</option>
                </select>
            </div>

            <div class="form-group">
                <label>Сложность</label>
                <select name="difficulty">
                    <option value="">Любая</option>
                    <option value="easy" <?php echo $filter_difficulty === 'easy' ? 'selected' : ''; ?>>Лёгкая</option>
                    <option value="medium" <?php echo $filter_difficulty === 'medium' ? 'selected' : ''; ?>>Средняя</option>
                    <option value="hard" <?php echo $filter_difficulty === 'hard' ? 'selected' : ''; ?>>Сложная</option>
                </select>
            </div>

            <div class="form-group">
                <label>Статус</label>
                <select name="status">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Все задачи</option>
                    <option value="solved" <?php echo $filter_status === 'solved' ? 'selected' : ''; ?>>Решённые</option>
                    <option value="unsolved" <?php echo $filter_status === 'unsolved' ? 'selected' : ''; ?>>Нерешённые</option>
                </select>
            </div>
        </div>

        <div class="page-actions">
            <button type="submit" class="btn btn-primary">Применить фильтр</button>
            <a href="tasks.php" class="btn btn-secondary">Сбросить</a>
        </div>
    </form>
</div>

<h2>Доступные задачи (<?php echo count($tasks); ?>)</h2>

<?php if (empty($tasks)): ?>
    <div class="empty-state">
        <p>По выбранным фильтрам задачи не найдены</p>
    </div>
<?php else: ?>
    <div style="display:grid; gap:15px; margin-top:20px;">
        <?php foreach ($tasks as $task): ?>
            <div class="task-card">
                <div style="display:flex; justify-content:space-between; align-items:start; gap:20px; flex-wrap:wrap;">
                    <div style="flex:1;">
                        <h3><?php echo htmlspecialchars($task['title']); ?></h3>

                        <p style="color:#666; margin-top:8px;">
                            <strong>Тип:</strong>
                            <?php echo htmlspecialchars($type_names[$task['task_type']] ?? $task['task_type']); ?>
                            |
                            <strong>Учитель:</strong>
                            <?php echo htmlspecialchars($task['teacher_name'] ?? '—'); ?>
                        </p>

                        <p style="margin-top:15px;">
                            <?php
                            $preview = $task['task_condition'] ?? '';
                            echo nl2br(htmlspecialchars(mb_substr($preview, 0, 220)));
                            ?>
                            <?php if (mb_strlen($preview) > 220): ?>...<?php endif; ?>
                        </p>

                        <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                            <?php echo getDifficultyBadge($task['difficulty']); ?>

                            <?php if ((int)($task['is_completed'] ?? 0) === 1): ?>
                                <span class="badge badge-success">
                                    Решена
                                    <?php if (isset($task['score'])): ?>
                                        (<?php echo (int)$task['score']; ?> баллов)
                                    <?php endif; ?>
                                </span>
                            <?php elseif (!empty($task['assigned_id'])): ?>
                                <span class="badge badge-warning">
                                    В работе (попыток: <?php echo (int)$task['attempts']; ?>)
                                </span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Ещё не решалась</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="min-width:140px; text-align:right;">
                        <a href="solve_task.php?task_id=<?php echo (int)$task['id']; ?>" class="btn btn-primary">
                            <?php
                            if ((int)($task['is_completed'] ?? 0) === 1) {
                                echo 'Решить снова';
                            } elseif (!empty($task['assigned_id'])) {
                                echo 'Продолжить';
                            } else {
                                echo 'Решить';
                            }
                            ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>