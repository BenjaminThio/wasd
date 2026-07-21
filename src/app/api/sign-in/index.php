<?php
    header('Content-Type: application/json');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    $user = [
        "email" => $_GET["email"],
        "password"=> $_GET["password"],
        
        "emailError"=> false,
        "passwordError"=> false,
    ];

    if (!str_ends_with($user["email"],"@gmail.com")) {
        $user["emailError"] = true;
    }

    if (strlen($user["password"]) <8 || strlen($user["password"])>32) {
        $user['passwordError'] = true;
    }

    echo json_encode($user);

    exit;
?>

