<?php
    require_once 'config.php';
    require_once 'models/Page.php';
    require_once 'lib/utils.php';

    $route = $_GET['page'] ?? '';

    $appDir = __DIR__ . '/app';
    $targetFolder = empty($route) ? $appDir : "$appDir/$route";
    $fileToLoad = "$targetFolder/index.php";
    $page = new Page();

    if (file_exists($fileToLoad))
    {
        $realPath = realpath($fileToLoad);

        if (!$realPath || !str_starts_with($realPath, realpath($appDir)))
        {
            $fileToLoad = null;
            Console::log('Malicious Attack Detected.');
            return;
        }
    }
    else
    {
        $fileToLoad = null;
    }

    $cssToLoad = "$targetFolder/style.css";

    if (file_exists($cssToLoad))
    {
        $page->setCssUrl(BASE_URL . "/src/app/$route/style.css");
    }

    ob_start();

    if ($fileToLoad === null)
    {
        $page->setTitle('404 Not Found');
        echo <<<HTML
            <h1 style="margin:0;min-height:100vh;display:flex;justify-content:center;align-items:center;">404 - Page Not Found!</h1>
        HTML;
    }
    else
    {
        if (!empty($route))
        {
            $page->setTitle($route);
        }
        require $fileToLoad;
    }

    $page->setContent(ob_get_clean());

    require 'layout.php';
?>