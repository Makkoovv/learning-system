<?php
require_once '../includes/functions.php';
checkUserType(['admin']);

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostCsrf();

    // Добавление пользователя
    if (isset($_POST['add_user'])) {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $city      = trim($_POST['city'] ?? '');
        $school    = trim($_POST['school'] ?? '');
        $user_type = $_POST['user_type'] ?? 'student';
        $pass_raw  = $_POST['password'] ?? '';

        if ($city === '' || $school === '') {
            $_SESSION['error'] = 'Город и школа обязательны для заполнения';
            header('Location: users.php');
            exit();
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = 'Пользователь с таким логином или email уже существует';
            header('Location: users.php');
            exit();
        }

        $password = password_hash($pass_raw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (username, password, email, full_name, city, school, user_type)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([$username, $password, $email, $full_name, $city, $school, $user_type])) {
            $_SESSION['success'] = 'Пользователь добавлен';
        } else {
            $_SESSION['error'] = 'Ошибка при добавлении';
        }

        header('Location: users.php');
        exit();
    }

    // Переключение статуса
    if (isset($_POST['toggle_user'])) {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0);

        if ($id > 0 && ($status === 0 || $status === 1)) {
            $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $_SESSION['success'] = 'Статус пользователя изменен';
        } else {
            $_SESSION['error'] = 'Некорректные данные';
        }

        header('Location: users.php');
        exit();
    }

    // Сброс пароля
    if (isset($_POST['reset_password'])) {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $new_password = bin2hex(random_bytes(4));
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $id]);

            $_SESSION['success'] = 'Пароль сброшен. Новый пароль: ' . $new_password;
        } else {
            $_SESSION['error'] = 'Некорректный ID';
        }

        header('Location: users.php');
        exit();
    }

    // Удаление пользователя
    if (isset($_POST['delete_user'])) {
        $id = (int)($_POST['id'] ?? 0);
        $current_admin_id = (int)($_SESSION['user_id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['error'] = 'Некорректный ID пользователя';
            header('Location: users.php');
            exit();
        }

        if ($id === $current_admin_id) {
            $_SESSION['error'] = 'Нельзя удалить самого себя';
            header('Location: users.php');
            exit();
        }

        // Проверим, существует ли пользователь
        $stmt = $conn->prepare("SELECT id, user_type FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['error'] = 'Пользователь не найден';
            header('Location: users.php');
            exit();
        }

        // Удаляем связанные попытки через assigned_tasks этого пользователя
        $stmt = $conn->prepare("
            DELETE ta
            FROM task_attempts ta
            INNER JOIN assigned_tasks at ON ta.assigned_task_id = at.id
            WHERE at.student_id = ?
        ");
        $stmt->execute([$id]);

        // Удаляем назначения задач этому пользователю
        $stmt = $conn->prepare("DELETE FROM assigned_tasks WHERE student_id = ?");
        $stmt->execute([$id]);

        // Если пользователь был teacher, отвязываем его задачи от автора
        // Либо можно удалять задачи учителя, но безопаснее сохранить задачи
        $stmt = $conn->prepare("UPDATE tasks SET created_by = NULL WHERE created_by = ?");
        try {
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            // Если created_by NOT NULL и UPDATE невозможен, просто продолжаем к удалению ниже не доходя
            // но лучше честно сообщить
            $_SESSION['error'] = 'Невозможно удалить пользователя: сначала удалите или переназначьте его задачи';
            header('Location: users.php');
            exit();
        }

        // Удаляем пользователя
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = 'Пользователь удален';
        header('Location: users.php');
        exit();
    }
}

$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $result ? $result->fetchAll() : [];

$page_title = 'Управление пользователями';
$page_heading = 'Управление пользователями';
$page_subtitle = '';

require_once '../includes/header.php';
?>

<div style="margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px;">
    <h3>Добавить пользователя</h3>

    <form method="POST">
        <?php echo csrfField(); ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>ФИО</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Город</label>
                <input type="text" name="city" required>
            </div>

            <div class="form-group">
                <label>Школа</label>
                <input type="text" name="school" required>
            </div>

            <div class="form-group">
                <label>Тип</label>
                <select name="user_type" required>
                    <option value="student">Студент</option>
                    <option value="teacher">Преподаватель</option>
                    <option value="admin">Администратор</option>
                </select>
            </div>
        </div>

        <button type="submit" name="add_user" class="btn btn-primary">Добавить пользователя</button>
    </form>
</div>

<div style="background: white; padding: 20px; border-radius: 10px;">
    <h3>Все пользователи (<?php echo count($users); ?>)</h3>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Логин</th>
                <th>ФИО</th>
                <th>Email</th>
                <th>Город</th>
                <th>Школа</th>
                <th>Тип</th>
                <th>Статус</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo (int)$user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['city'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($user['school'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                        <td><?php echo getStatusBadge($user['is_active']); ?></td>
                        <td><?php echo formatDate($user['created_at'], 'd.m.Y'); ?></td>
                        <td style="white-space: nowrap;">
                            <form method="POST" style="display:inline;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="toggle_user" value="1">
                                <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                <button type="submit" class="btn btn-small <?php echo $user['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                    <?php echo $user['is_active'] ? 'Деакт.' : 'Акт.'; ?>
                                </button>
                            </form>

                            <form method="POST" style="display:inline;" onsubmit="return confirm('Сбросить пароль пользователю?');">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="reset_password" value="1">
                                <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                <button type="submit" class="btn btn-small btn-warning">Сброс</button>
                            </form>

                            <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить пользователя? Это действие необратимо.');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="delete_user" value="1">
                                    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                    <button type="submit" class="btn btn-small btn-danger">Удалить</button>
                                </form>
                            <?php else: ?>
                                <span class="badge badge-secondary">Вы</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" style="text-align:center;">Нет пользователей</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>