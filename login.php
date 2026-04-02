<?php
require_once 'includes/functions.php';

if (isLoggedIn()) {
    $user_type = $_SESSION['user_type'] ?? '';
    switch ($user_type) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: teacher/dashboard.php');
            break;
        case 'student':
            header('Location: student/dashboard.php');
            break;
        default:
            header('Location: index.php');
    }
    exit();
}

$error = '';

$db = new Database();
$pdo = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePostCsrf();

    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';

    // ✅ убрали user_type
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];

            // ✅ редирект по роли из БД
            if ($user['user_type'] === 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($user['user_type'] === 'teacher') {
                header('Location: teacher/dashboard.php');
            } else {
                header('Location: student/dashboard.php');
            }
            exit();

        } else {
            $error = 'Неверный пароль';
        }
    } else {
        $error = 'Пользователь не найден или неактивен';
    }
}

$page_title = 'Вход';
$page_heading = 'Вход в систему';
$page_subtitle = 'Подготовка к ОГЭ — задачи на движение';

require_once 'includes/header.php';
?>

<div class="auth-form">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php echo csrfField(); ?>

        <!-- ❌ УДАЛИЛИ выбор роли -->

        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Войти</button>
    </form>

    <p style="text-align: center; margin-top: 20px;">
        Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>