<?php
header('Content-Type: application/json');

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$user = [
    'name' => $_GET['name'],
    'email' => $_GET['email']
];

echo json_encode($user);

exit;
?>