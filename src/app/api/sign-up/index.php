<?php
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$info = [
    "username" => $_GET["username"],
    "email" => $_GET["email"],
    "password" => $_GET["password"],
    "confirmPass" => $_GET["confirmPass"],

    "usernameError" => false,
    "emailError" => false,
    "passwordError" => false,
    "confirmPassError" => false,
];

if (preg_match('/^[a-zA-Z0-9_]{3,15}$/', $info["username"])) {
    echo "Valid username!";
}

if (!str_ends_with($info['email'], "@gmail.com")) {
    $info["email"] = true;
}

if (strlen($info["password"] < 8 || $info["password"] > 32)) {
    $info["password"] = false;
}

if (password_verify($info["password"], $info["confirmPass"])) {
    echo "Password is correct";
} else {
    echo "Password is incorrect";
}

echo json_encode($info);

exit;
?>