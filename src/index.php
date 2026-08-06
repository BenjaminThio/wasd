<?php
    require_once './config.php';
    require_once './models/Page.php';
    require_once './lib/utils.php';

    // Remove leading/trailing slashes for clean matching
    $route = trim($_GET['page'] ?? '', '/');

    $appDir = __DIR__ . '/app';
    $targetFolder = null;

    // Next.js Route Group Resolver
    if (empty($route) && is_file("$appDir/index.php")) {
        $targetFolder = $appDir;
    } elseif (!empty($route) && is_dir("$appDir/$route")) {
        $targetFolder = "$appDir/$route"; // Direct match (e.g., app/store)
    } else {
        // Search one level deep for Route Groups like (auth)
        $groups = glob("$appDir/(*)", GLOB_ONLYDIR);
        foreach ($groups as $group) {
            if (empty($route) && is_file("$group/index.php")) {
                $targetFolder = $group;
                break;
            } elseif (!empty($route) && is_dir("$group/$route")) {
                $targetFolder = "$group/$route"; // Found inside group (e.g., app/(auth)/sign-in)
                break;
            }
        }
    }

    $fileToLoad = $targetFolder ? "$targetFolder/index.php" : null;
    $page = new Page();

    // Security Verification
    if ($fileToLoad && file_exists($fileToLoad)) {
        $realPath = realpath($fileToLoad);
        if (!$realPath || !str_starts_with($realPath, realpath($appDir))) {
            $fileToLoad = null;
            Console::error('Malicious Attack Detected.');
        }
    } else {
        $fileToLoad = null;
    }

    // Dynamic CSS Loading
    if ($targetFolder && file_exists("$targetFolder/style.css")) {
        // Calculate relative path for the CSS file link
        $relPath = str_replace(realpath(__DIR__), '', realpath($targetFolder));
        $relPath = str_replace('\\', '/', $relPath);

        // The modification time doubles as a cache key: browsers keep serving
        // the old stylesheet after an edit otherwise, which is exactly the kind
        // of "my CSS did not update" confusion that costs an afternoon.
        $stamp = @filemtime("$targetFolder/style.css") ?: 0;

        $page->setCssUrl(BASE_URL . "/src" . $relPath . "/style.css?v=$stamp");
    }

    // Buffer the inner page content
    ob_start();

    if ($fileToLoad === null) {
        // FIX: Tell the browser (and JavaScript) this is an actual 404 error!
        http_response_code(404);
        
        $page->setTitle('404 Not Found');
        echo "<h1 style='margin:0;min-height:100vh;display:flex;justify-content:center;align-items:center;'>404 - Page Not Found!</h1>";
    } else {
        if (!empty($route)) {
            // "contact/support" -> "Support", "sign-in" -> "Sign In".
            $segment = basename($route);
            $page->setTitle(ucwords(str_replace('-', ' ', strtolower($segment))));
        }

        // A page may replace that with something more specific while it renders.
        require $fileToLoad;
    }

    $page->setContent(ob_get_clean());

    // The shell that follows must define its own icons rather than lean on any
    // the page body happened to define - a soft navigation replaces the body.
    //
    // Guarded rather than required: the auth pages include Icon.php with a
    // plain require, so pulling it in here would redeclare the class. If the
    // body never loaded it there is nothing to reset either way.
    if (class_exists('Icon', false)) {
        Icon::resetDefinitions();
    }

    // Nested Layout Finder
    $layoutToLoad = __DIR__ . '/layout.php'; // Default fallback layout
    $current = $targetFolder;
    
    // Crawl up the directories to find the closest layout.php
    while ($current && realpath($current) && str_starts_with(realpath($current), realpath($appDir))) {
        if (is_file("$current/layout.php")) {
            $layoutToLoad = "$current/layout.php";
            break; 
        }
        $current = dirname($current);
    }

    // Single Page App (SPA) Engine
    $isSpaRequest = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';

    // If JavaScript asked for a seamless navigation, skip the layout
    if ($isSpaRequest) {
        header('Content-Type: application/json');
        echo json_encode([
            'title' => $page->getTitle(),
            'css' => $page->getCssUrl(),
            'html' => $page->getContent(),
            'layout' => $layoutToLoad, // JS needs this to know if it should soft-swap or hard-reload
            'reload' => $page->requiresFullLoad() // pages whose headers matter
        ]);
        exit;
    }

    // Standard initial load prints the layout wrapper
    require $layoutToLoad;
?>