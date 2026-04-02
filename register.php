<?php
require_once 'includes/functions.php';
if (isLoggedIn()) redirect('index.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    requirePostCsrf();

    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $email = sanitizeInput($_POST['email'] ?? '');
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $school = sanitizeInput($_POST['school'] ?? '');
    $user_type = $_POST['user_type'] ?? 'student';
    $confirmation_code = $_POST['confirmation_code'] ?? '';

    if ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif (empty($city) || empty($school)) {
        $error = 'Город и школа обязательны для заполнения';
    } elseif ($user_type === 'teacher' && $confirmation_code !== TEACHER_CONFIRMATION_CODE) {
        $error = 'Неверный код подтверждения для преподавателя';
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            $error = 'Пользователь с таким логином или email уже существует';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (username, password, email, full_name, city, school, user_type)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$username, $hashed, $email, $full_name, $city, $school, $user_type])) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
            } else {
                $error = 'Ошибка при регистрации';
            }
        }
    }
}

$page_title = 'Регистрация';
$page_heading = 'Регистрация';
$page_subtitle = 'Создайте аккаунт для работы в системе';

require_once 'includes/header.php';
?>

<div class="auth-form" style="max-width:700px;">

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php echo csrfField(); ?>

        <div class="form-group">
            <label>Полное имя</label>
            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
            <div class="form-group">
                <label>Город</label>
                <input type="text" name="city" required value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Школа</label>
                <input type="text" name="school" required value="<?php echo htmlspecialchars($_POST['school'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Тип аккаунта</label>

            <input type="hidden" name="user_type" id="user_type" value="<?php echo htmlspecialchars($_POST['user_type'] ?? 'student'); ?>">

            <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap;">
                <button type="button" class="btn" id="btn-student" onclick="selectRole('student')">
                    Ученик
                </button>

                <button type="button" class="btn" id="btn-teacher" onclick="selectRole('teacher')">
                    Преподаватель
                </button>
            </div>
        </div>

        <div class="form-group" id="teacher-code-block" style="display:none;">
            <label>Код подтверждения преподавателя</label>

            <input type="text" name="confirmation_code" placeholder="Введите код" value="<?php echo htmlspecialchars($_POST['confirmation_code'] ?? ''); ?>">

            <small style="display:block;margin-top:6px;color:#666;">
                Для регистрации преподавателя требуется специальный код.
            </small>

            <div style="margin-top:12px;padding:12px;border-radius:8px;background:#f1f5f9;">
                <small style="display:block;margin-bottom:8px;color:#444;">
                    Если у вас нет этого кода, свяжитесь с нами:
                </small>

                <a href="https://e.mail.ru/compose/?to=admin@mail.ru"
                   target="_blank"
                   class="btn btn-primary"
                   style="text-decoration:none;display:inline-block;">
                    ✉ Написать нам на почту
                </a>
            </div>
        </div>

        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Подтвердите пароль</label>
            <input type="password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;">
            Зарегистрироваться
        </button>
    </form>

    <p style="text-align:center;margin-top:20px;">
        Уже есть аккаунт?
        <a href="login.php">Войти</a>
    </p>
</div>

<script>
function selectRole(role) {
    document.getElementById('user_type').value = role;

    const studentBtn = document.getElementById('btn-student');
    const teacherBtn = document.getElementById('btn-teacher');
    const teacherBlock = document.getElementById('teacher-code-block');

    studentBtn.classList.remove('btn-primary', 'btn-secondary');
    teacherBtn.classList.remove('btn-primary', 'btn-secondary');

    if (role === 'teacher') {
        teacherBtn.classList.add('btn-primary');
        studentBtn.classList.add('btn-secondary');
        teacherBlock.style.display = 'block';
    } else {
        studentBtn.classList.add('btn-primary');
        teacherBtn.classList.add('btn-secondary');
        teacherBlock.style.display = 'none';
    }
}

selectRole('<?php echo htmlspecialchars($_POST['user_type'] ?? 'student'); ?>');
</script>

<?php require_once 'includes/footer.php'; ?>