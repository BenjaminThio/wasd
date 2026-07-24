<?php
require_once __DIR__ . '/../../lib/Auth.php';

// Force internal login using hardcoded dev user info
Auth::loginDevUser();

// Instantly redirect back to home
header('Location: ' . BASE_URL . '/');
exit;
?>