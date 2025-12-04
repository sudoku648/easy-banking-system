<?php

declare(strict_types=1);

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// Serve React app for non-API routes
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (!str_starts_with($requestUri, '/api') && !str_starts_with($requestUri, '/build')) {
    // Check if it's not a file that exists
    $filePath = __DIR__ . $requestUri;
    if (!is_file($filePath)) {
        // Serve React app
        $reactIndexPath = __DIR__ . '/build/index.html';
        if (file_exists($reactIndexPath)) {
            readfile($reactIndexPath);
            exit;
        }
    }
}

return fn (array $context): Kernel => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
