<?php
    // Use __DIR__ to lock the path so it never breaks when called from subfolders
    require_once __DIR__ . '/lib/utils.php';

    // Safely calculate the Base URL based on the physical folder structure
    $documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $projectRoot = str_replace('\\', '/', __DIR__); 
    
    // Remove the '/src' part from the project root to get the base 'wasd' path
    $baseUrl = str_replace('/src', '', str_replace($documentRoot, '', $projectRoot));

    if ($baseUrl === '')
    {
        $baseUrl = '/';
    }

    define('BASE_URL', $baseUrl);
?>