<?php
// api/index.php - Front router for Vercel Serverless execution

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = urldecode($uri);

// Normalize path (strip leading slash)
$path = trim($uri, '/');

// Default root request -> load main index.php
if (empty($path) || $path === '/') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    require __DIR__ . '/../index.php';
    exit;
}

$rootPath = realpath(__DIR__ . '/..');
$targetFile = realpath($rootPath . '/' . $path);

// Security: Ensure requested file is inside project root and exists
if ($targetFile && strpos($targetFile, $rootPath) === 0 && file_exists($targetFile)) {
    if (is_dir($targetFile)) {
        $indexFile = $targetFile . DIRECTORY_SEPARATOR . 'index.php';
        if (file_exists($indexFile)) {
            $_SERVER['SCRIPT_NAME'] = '/' . $path . '/index.php';
            $_SERVER['PHP_SELF'] = '/' . $path . '/index.php';
            chdir($targetFile);
            require $indexFile;
            exit;
        }
    } elseif (pathinfo($targetFile, PATHINFO_EXTENSION) === 'php') {
        $_SERVER['SCRIPT_NAME'] = '/' . $path;
        $_SERVER['PHP_SELF'] = '/' . $path;
        chdir(dirname($targetFile));
        require $targetFile;
        exit;
    }
}

// Fallback to main index.php or 404
http_response_code(404);
echo "404 - Page Not Found";
