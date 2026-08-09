<?php
require_once __DIR__ . "/../../../models/Users.php";

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$email = $_GET["email"];
$password = $_GET["password"];

$user = Users::getByEmail($email);

if ($user === null)
{
    echo json_encode([
        "success" => false,
        "message" => "Email not found.",
        "error" => "email"
    ]);
    exit;
}

if (!password_verify($password, $user->getPassword()))
{
    echo json_encode([
        "success" => false,
        "message" => "Incorrect password.",
        "error" => "password"
    ]);
    exit;
}

echo json_encode(["success" => true]);
exit;
?>