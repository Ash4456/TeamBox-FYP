<?php
session_start();


if (isset($_POST['userId'])) {
    $userId = $_POST['userId'];
} else {
    $userId = '';
}

if (isset($_POST['password'])) {
    $password = $_POST['password'];
} else {
    $password = '';
}


$users = [
    's1' => [
        'password'   => '12345',
        'role'       => 'student',
        'name'       => 'Student A',
        'student_id' => 1
    ],
    't1' => [
        'password'   => '12345',
        'role'       => 'teacher',
        'name'       => 'Mr. Tan',
        'teacher_id' => 1
    ],
];


if (!isset($users[$userId]) || $users[$userId]['password'] !== $password) {

    header('Location: login.php?error=1');
    exit;
}

$user = $users[$userId];


if ($user['role'] === 'student') {

    $_SESSION['role']       = 'student';
    $_SESSION['userId']     = $userId;
    $_SESSION['student_id'] = $user['student_id'];
    $_SESSION['full_name']  = $user['name'];

    header('Location: student_index.php');
    exit;

} elseif ($user['role'] === 'teacher') {

    $_SESSION['role']       = 'teacher';
    $_SESSION['userId']     = $userId;
    $_SESSION['teacher_id'] = $user['teacher_id'];
    $_SESSION['full_name']  = $user['name'];

    header('Location: teacher_index.php');
    exit;
}


header('Location: login.php?error=1');
exit;
