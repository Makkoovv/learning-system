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
            header('Location: login.php');
    }
    exit();
}

$page_title = SITE_NAME;
$page_heading = SITE_NAME;
$page_subtitle = 'Интеллектуальная система обучения';

require_once 'includes/header.php';
?>

<div class="task-card" style="text-align:center; padding:40px 30px;">
    <h2 style="margin-bottom:20px;">Добро пожаловать!</h2>

    <p style="max-width:700px; margin:0 auto; color:#555; font-size:18px;">
        Онлайн-платформа для подготовки к ОГЭ по математике.
        Решай задачи на движение, тренируйся в удобном формате и отслеживай свой прогресс.
    </p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Для учеников</h3>
        <p>Решение задач, тренировки и история попыток</p>
    </div>

    <div class="stat-card">
        <h3>Для преподавателей</h3>
        <p>Создание и публикация задач в общий банк</p>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>