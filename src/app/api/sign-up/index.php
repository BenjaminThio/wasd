<?php
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

const MIN_USERNAME_LENGTH = 3;
const MAX_USERNAME_LENGTH = 32;
const MIN_PASSWORD_LENGTH = 8;
const MAX_PASSWORD_LENGTH = 32;

$username = $_GET["username"];
$email = $_GET["email"];
$password = $_GET["password"];
$confirmPass =  $_GET["confirmPass"];

if (strlen($username) < MIN_USERNAME_LENGTH || strlen($username) > MAX_USERNAME_LENGTH) {
    echo json_encode([
        "success" => false,
        "message" => "The username length should be between " . MIN_USERNAME_LENGTH . " to " . MAX_USERNAME_LENGTH . " characters.",
        "error" => "username"
    ]);
    exit;
}

if (!str_ends_with($email, "@gmail.com")) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email.",
        "error" => "email"
    ]);
    exit;
}

if (strlen($password) < MIN_PASSWORD_LENGTH || strlen($password) > MAX_PASSWORD_LENGTH) {
    echo json_encode([
        "success" => false,
        "message" => "The password length should be between " . MIN_PASSWORD_LENGTH . " to " . MAX_PASSWORD_LENGTH  . " characters.",
        "error" => "password"
    ]);
    exit;
}

if (!preg_match('/[a-z]/', $password)) {
    echo json_encode([
        "success" => false,
        "message" => "The password should include at least 1 lowercase character.",
        "error" => "password"
    ]);
    exit;
}

if (!preg_match('/[A-Z]/', $password)) {
    echo json_encode([
        "success" => false,
        "message" => "The password should include at least 1 uppercase character.",
        "error" => "password"
    ]);
    exit;
}

if (!preg_match('/[0-9]/', $password)) {
    echo json_encode([
        "success" => false,
        "message" => "The password should include at least 1 digit.",
        "error" => "password"
    ]);
    exit;
}

if (!preg_match('/[^\w]/', $password)) {
    echo json_encode([
        "success" => false,
        "message" => "The password should include at least 1 special character.",
        "error" => "password"
    ]);
    exit;
}

if ($password !== $confirmPass) {
    echo json_encode([
        "success" => false,
        "message" => "The password and confirmation password doesn't match.",
        "error" => "password"
    ]);
    exit;
}

echo json_encode(["success" => true]);
exit;
?>