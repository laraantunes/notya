<?php
session_start();

define('USERS_FILE', __DIR__ . '/data/users.json');

function getUsers() {
    if (!file_exists(USERS_FILE)) {
        return [];
    }
    return json_decode(file_get_contents(USERS_FILE), true) ?: [];
}

function saveUsers($users) {
    if (!is_dir(dirname(USERS_FILE))) {
        mkdir(dirname(USERS_FILE), 0755, true);
    }
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function hasUsers() {
    $users = getUsers();
    return !empty($users);
}

function registerUser($username, $password) {
    $users = getUsers();
    if (isset($users[$username])) {
        return false;
    }
    $users[$username] = [
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s')
    ];
    saveUsers($users);
    return true;
}

function login($username, $password) {
    $users = getUsers();
    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['user'] = $username;
        return true;
    }
    return false;
}

function updatePassword($username, $newPassword) {
    $users = getUsers();
    if (isset($users[$username])) {
        $users[$username]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        saveUsers($users);
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function logout() {
    session_destroy();
    header('Location: index.php');
    exit;
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: index.php?page=login');
        exit;
    }
}
