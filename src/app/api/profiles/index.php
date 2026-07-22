<?php
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';


// create a dictionary
$User = [
    "status" => "success",
    "name" => $username,
    "email" => $email,
    "password" => $password,
    "emailError" => false,
    "passwordError" => false
];

echo json_encode($User);

exit;

?>