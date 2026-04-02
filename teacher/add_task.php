<?php
require_once '../includes/functions.php';
checkUserType(['teacher', 'admin']);

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$error = '';
$is_edit = false;
$task_id = 0;

$task = [
    'title' => '',
    'task_type' => 'sblizhenie',
    'example_condition' => '',
    'example_solution' => '',
    'example_explanation' => '',
    'example_answer' => '',
    'task_condition' => '',
    'task_answer' => '',
    'difficulty' => 'medium',
    'hint1' => '',
    'hint2' => '',
    'correct_steps' => null
];

$correct_steps_text = '';

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $task_id = (int)$_GET['id'];

    if ($user_type === 'admin') {
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND created_by = ?");
        $stmt->execute([$task_id, $user_id]);
    }

    $found = $stmt->fetch();

    if ($found) {
        $task = array_merge($task, $found);
        $is_edit = true;

        if (!empty($task['correct_steps'])) {
            $decoded = json_decode($task['correct_steps'], true);
            if (is_array($decoded)) {
                $correct_steps_text = implode("\n", $decoded);
            }
        }
    } else {
        $_SESSION['error'] = 'Задача не найдена или нет прав на редактирование';
        header('Location: tasks.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostCsrf();

    $title = trim($_POST['title'] ?? '');
    $task_type = $_POST['task_type'] ?? 'sblizhenie';
    $difficulty = $_POST['difficulty'] ?? 'medium';

    // ✅ теперь необязательные
    $example_condition = trim($_POST['example_condition'] ?? '') ?: '';
    $example_solution = trim($_POST['example_solution'] ?? '') ?: '';
    $example_explanation = trim($_POST['example_explanation'] ?? '') ?: '';
    $example_answer = trim($_POST['example_answer'] ?? '') ?: '';

    $task_condition = trim($_POST['task_condition'] ?? '');
    $task_answer = trim($_POST['task_answer'] ?? '');

    $hint1 = trim($_POST['hint1'] ?? '');
    $hint2 = trim($_POST['hint2'] ?? '');
    $correct_steps_text = trim($_POST['correct_steps_text'] ?? '');

    $allowed_types = ['sblizhenie', 'udalenie', 'dogonka', 'vstrecha'];
    $allowed_difficulty = ['easy', 'medium', 'hard'];

    $correct_steps_array = [];
    if ($correct_steps_text !== '') {
        $lines = preg_split('/\r\n|\r|\n/', $correct_steps_text);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $correct_steps_array[] = $line;
            }
        }
    }
    $correct_steps_json = !empty($correct_steps_array) ? json_encode($correct_steps_array, JSON_UNESCAPED_UNICODE) : null;

    // ✅ убрали обязательность примера
    if (
        $title === '' ||
        $task_condition === '' ||
        $task_answer === ''
    ) {
        $error = 'Название, условие и ответ обязательны';
    } elseif (!in_array($task_type, $allowed_types, true)) {
        $error = 'Некорректный тип задачи';
    } elseif (!in_array($difficulty, $allowed_difficulty, true)) {
        $error = 'Некорректная сложность';
    } else {
        if (!empty($_POST['task_id'])) {
            $edit_id = (int)$_POST['task_id'];

            if ($user_type === 'admin') {
                $check = $conn->prepare("SELECT id FROM tasks WHERE id = ?");
                $check->execute([$edit_id]);
            } else {
                $check = $conn->prepare("SELECT id FROM tasks WHERE id = ? AND created_by = ?");
                $check->execute([$edit_id, $user_id]);
            }

            if (!$check->fetch()) {
                $error = 'Нет прав на редактирование задачи';
            } else {
                $stmt = $conn->prepare("
                    UPDATE tasks
                    SET title = ?,
                        task_type = ?,
                        example_condition = ?,
                        example_solution = ?,
                        example_explanation = ?,
                        example_answer = ?,
                        task_condition = ?,
                        task_answer = ?,
                        difficulty = ?,
                        hint1 = ?,
                        hint2 = ?,
                        correct_steps = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $title,
                    $task_type,
                    $example_condition,
                    $example_solution,
                    $example_explanation,
                    $example_answer,
                    $task_condition,
                    $task_answer,
                    $difficulty,
                    $hint1,
                    $hint2,
                    $correct_steps_json,
                    $edit_id
                ]);

                $_SESSION['success'] = 'Задача успешно обновлена';
                header('Location: tasks.php');
                exit();
            }
        } else {
            $stmt = $conn->prepare("
                INSERT INTO tasks (
                    title,
                    task_type,
                    example_condition,
                    example_solution,
                    example_explanation,
                    example_answer,
                    task_condition,
                    task_answer,
                    difficulty,
                    created_by,
                    correct_steps,
                    hint1,
                    hint2
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $task_type,
                $example_condition,
                $example_solution,
                $example_explanation,
                $example_answer,
                $task_condition,
                $task_answer,
                $difficulty,
                $user_id,
                $correct_steps_json,
                $hint1,
                $hint2
            ]);

            $_SESSION['success'] = 'Задача успешно создана и опубликована в общий список';
            header('Location: tasks.php');
            exit();
        }
    }

    $task = [
        'title' => $title,
        'task_type' => $task_type,
        'example_condition' => $example_condition,
        'example_solution' => $example_solution,
        'example_explanation' => $example_explanation,
        'example_answer' => $example_answer,
        'task_condition' => $task_condition,
        'task_answer' => $task_answer,
        'difficulty' => $difficulty,
        'hint1' => $hint1,
        'hint2' => $hint2,
        'correct_steps' => $correct_steps_json
    ];
}

$page_title = $is_edit ? 'Редактировать задачу' : 'Создать задачу';
$page_heading = $is_edit ? 'Редактирование задачи' : 'Создание задачи';
$page_subtitle = $is_edit ? 'Изменение существующей задачи' : 'Добавление новой задачи';

require_once '../includes/header.php';
?>

<div class="form-container">
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST">
    <?php echo csrfField(); ?>
    <?php if ($is_edit): ?>
        <input type="hidden" name="task_id" value="<?php echo (int)$task_id; ?>">
    <?php endif; ?>

    <div class="form-group">
        <label>Название задачи</label>
        <input type="text" name="title" required value="<?php echo htmlspecialchars($task['title']); ?>">
    </div>

    <div class="form-group">
        <label>Тип задачи</label>
        <select name="task_type" required>
            <option value="sblizhenie" <?php echo $task['task_type'] === 'sblizhenie' ? 'selected' : ''; ?>>Сближение</option>
            <option value="udalenie" <?php echo $task['task_type'] === 'udalenie' ? 'selected' : ''; ?>>Удаление</option>
            <option value="dogonka" <?php echo $task['task_type'] === 'dogonka' ? 'selected' : ''; ?>>Вдогонку</option>
            <option value="vstrecha" <?php echo $task['task_type'] === 'vstrecha' ? 'selected' : ''; ?>>Встреча</option>
        </select>
    </div>

    <div class="form-group">
        <label>Сложность</label>
        <select name="difficulty" required>
            <option value="easy" <?php echo $task['difficulty'] === 'easy' ? 'selected' : ''; ?>>Лёгкая</option>
            <option value="medium" <?php echo $task['difficulty'] === 'medium' ? 'selected' : ''; ?>>Средняя</option>
            <option value="hard" <?php echo $task['difficulty'] === 'hard' ? 'selected' : ''; ?>>Сложная</option>
        </select>
    </div>

    <!-- ✅ убрали required -->
    <div class="form-group">
        <label>Пример задачи</label>
        <textarea name="example_condition"><?php echo htmlspecialchars($task['example_condition']); ?></textarea>
    </div>

    <div class="form-group">
        <label>Пример решения</label>
        <textarea name="example_solution"><?php echo htmlspecialchars($task['example_solution']); ?></textarea>
    </div>

    <div class="form-group">
        <label>Пояснение к примеру</label>
        <textarea name="example_explanation"><?php echo htmlspecialchars($task['example_explanation']); ?></textarea>
    </div>

    <div class="form-group">
        <label>Ответ к примеру</label>
        <input type="text" name="example_answer" value="<?php echo htmlspecialchars($task['example_answer']); ?>">
    </div>

    <div class="form-group">
        <label>Основная задача</label>
        <textarea name="task_condition" required><?php echo htmlspecialchars($task['task_condition']); ?></textarea>
    </div>

    <div class="form-group">
        <label>Ответ</label>
        <input type="text" name="task_answer" required value="<?php echo htmlspecialchars($task['task_answer']); ?>">
    </div>

    <div class="form-group">
        <label>Подсказка 1</label>
        <textarea name="hint1"><?php echo htmlspecialchars($task['hint1']); ?></textarea>
    </div>

    <div class="form-group">
        <label>Подсказка 2</label>
        <textarea name="hint2"><?php echo htmlspecialchars($task['hint2']); ?></textarea>
    </div>

    <button class="btn btn-primary">Сохранить</button>
</form>
</div>

<?php require_once '../includes/footer.php'; ?>