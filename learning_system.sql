-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Мар 29 2026 г., 11:07
-- Версия сервера: 10.8.4-MariaDB
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `learning_system`
--

-- --------------------------------------------------------

--
-- Структура таблицы `assigned_tasks`
--

CREATE TABLE `assigned_tasks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `assigned_date` timestamp NULL DEFAULT current_timestamp(),
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_date` timestamp NULL DEFAULT NULL,
  `score` int(11) DEFAULT 0,
  `attempts` int(11) DEFAULT 0,
  `last_attempt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `assigned_tasks`
--

INSERT INTO `assigned_tasks` (`id`, `student_id`, `task_id`, `teacher_id`, `assigned_date`, `is_completed`, `completed_date`, `score`, `attempts`, `last_attempt`) VALUES
(59, 5, 18, 9, '2026-03-25 12:35:57', 1, '2026-03-25 12:36:13', 60, 3, '2026-03-25 12:36:13'),
(60, 5, 18, 9, '2026-03-25 12:36:37', 0, NULL, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `task_type` enum('sblizhenie','udalenie','dogonka','vstrecha') NOT NULL,
  `example_condition` text NOT NULL,
  `example_solution` text NOT NULL,
  `example_explanation` text NOT NULL,
  `example_answer` varchar(100) NOT NULL,
  `task_condition` text NOT NULL,
  `task_answer` varchar(100) NOT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `correct_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`correct_steps`)),
  `hint1` text DEFAULT NULL,
  `hint2` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `task_type`, `example_condition`, `example_solution`, `example_explanation`, `example_answer`, `task_condition`, `task_answer`, `difficulty`, `created_by`, `created_at`, `correct_steps`, `hint1`, `hint2`) VALUES
(18, 'Из пункта А в пункт Б', 'sblizhenie', '', '', '', '', 'Из двух городов, расстояние между которыми 240 км, одновременно навстречу друг другу выехали автомобиль и мотоцикл. Скорость автомобиля 80 км/ч, а скорость мотоцикла 40 км/ч. Через сколько часов они встретятся?', '2', 'medium', 9, '2026-03-25 12:34:36', NULL, 'Чтобы найти время до встречи при движении навстречу друг другу, нужно разделить расстояние между ними на скорость их сближения.', 'Скорость сближения равна сумме скоростей участников движения');

-- --------------------------------------------------------

--
-- Структура таблицы `task_attempts`
--

CREATE TABLE `task_attempts` (
  `id` int(11) NOT NULL,
  `assigned_task_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL,
  `student_answer` varchar(100) NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `attempted_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `task_attempts`
--

INSERT INTO `task_attempts` (`id`, `assigned_task_id`, `attempt_number`, `student_answer`, `is_correct`, `attempted_at`) VALUES
(88, 59, 1, '1', 0, '2026-03-25 12:36:01'),
(89, 59, 2, '1', 0, '2026-03-25 12:36:09'),
(90, 59, 3, '2', 1, '2026-03-25 12:36:13');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `full_name` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL DEFAULT '',
  `school` varchar(200) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `user_type`, `full_name`, `city`, `school`, `created_at`, `is_active`) VALUES
(1, 'admin', '$2y$10$RAZl5Do9SUnZ5mPU9XSyvO/XsqmmtExPTnkbyetyWSwF24ost2X0K', 'admin@system.local', 'admin', 'Администратор', 'Москва', 'Центр образования', '2026-02-18 11:25:19', 1),
(5, 'ученик новый', '$2y$10$1pG02TiDz8ZmzwIjMcumEuZcVKcKKf9tJb5EqUHYmBTnBaHmaC9Wy', 'uchenik@mail.ru', 'student', 'qwerty', 'Новосибирск', 'Школа 54', '2026-03-10 09:19:28', 1),
(9, 'prepod', '$2y$10$DiHCMDsehq9bLBR.4T8GoOMiu8cw8/d8Wf4gMfoluC8AMjjGAQ3B6', 'prepod@mail.ru', 'teacher', 'Преподаватель Преподаватель Преподаватель', 'Новокузнецк', 'Центр образования', '2026-03-17 11:24:32', 1),
(10, 'qwq', '$2y$10$JArlmCG3UuBQ2G3Ur1J8VOCTn1xz67KGOagMRhQd6BwOwIp3YMD8u', 'aaa@mail.ru', 'student', 'Фамилия Имя Отчество', 'Новокузнецк', 'Школа №42', '2026-03-17 11:36:04', 1);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `assigned_tasks`
--
ALTER TABLE `assigned_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `idx_student_status_date` (`student_id`,`is_completed`,`assigned_date`),
  ADD KEY `idx_student_lastattempt` (`student_id`,`last_attempt`),
  ADD KEY `idx_teacher_status_date` (`teacher_id`,`is_completed`,`assigned_date`);

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `task_type` (`task_type`),
  ADD KEY `idx_created_by_date` (`created_by`,`created_at`),
  ADD KEY `idx_task_type` (`task_type`);

--
-- Индексы таблицы `task_attempts`
--
ALTER TABLE `task_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_task_id` (`assigned_task_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `assigned_tasks`
--
ALTER TABLE `assigned_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `task_attempts`
--
ALTER TABLE `task_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `assigned_tasks`
--
ALTER TABLE `assigned_tasks`
  ADD CONSTRAINT `assigned_tasks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assigned_tasks_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assigned_tasks_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `task_attempts`
--
ALTER TABLE `task_attempts`
  ADD CONSTRAINT `task_attempts_ibfk_1` FOREIGN KEY (`assigned_task_id`) REFERENCES `assigned_tasks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
