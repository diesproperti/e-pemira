<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = base_path('app/Views/' . $view . '.php');
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View file tidak ditemukan: {$view}");
        }

        if ($layout === null) {
            require $viewFile;
            return;
        }

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        $layoutFile = base_path('app/Views/' . $layout . '.php');
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }

        require $layoutFile;
    }

    protected function redirect(string $path): never
    {
        $target = str_starts_with($path, 'http') ? $path : url($path);
        header('Location: ' . $target);
        exit;
    }

    protected function json(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

