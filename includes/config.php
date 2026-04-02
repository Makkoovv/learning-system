<?php
session_start();

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'learning_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Настройки приложения
define('SITE_NAME', 'Подготовка к ОГЭ - Задачи на движение');
define('MAX_ATTEMPTS', 3);
define('TEACHER_CONFIRMATION_CODE', 'TEACHER2024');

// Базовый путь (для редиректов)
define('BASE_PATH', ''); // если проект в корне домена: '' ; если в /learning-system: '/learning-system'

// DEBUG (на проде поставь false)
define('DEBUG', true);

error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');
?>