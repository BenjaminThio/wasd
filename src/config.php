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

    /*
     * Error reporting.
     *
     * A PHP notice or an Xdebug trace printed into the page hands an attacker
     * absolute file paths, the database structure and sometimes query text.
     * Errors are therefore hidden unless APP_DEBUG is explicitly turned on in
     * .env - the safe state is the default, and a missing .env key cannot
     * accidentally expose anything.
     *
     * Nothing is silenced: everything still goes to the PHP error log.
     */
    require_once __DIR__ . '/lib/Env.php';

    $appDebug = strtolower(trim((string)Env::get('APP_DEBUG', 'false')));
    define('APP_DEBUG', in_array($appDebug, ['1', 'true', 'yes', 'on'], true));

    ini_set('display_errors', APP_DEBUG ? '1' : '0');
    ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);

    if (!APP_DEBUG && extension_loaded('xdebug')) {
        // Xdebug prints its own HTML trace regardless of display_errors.
        @ini_set('xdebug.mode', 'off');
    }
?>