<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view);
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean() ?: '';

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = BASE_PATH . '/app/Views/' . str_replace('.', '/', $layout) . '.php';
        require $layoutFile;
    }

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
