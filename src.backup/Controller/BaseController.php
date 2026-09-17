<?php
namespace Src\Controller;

class BaseController
{
    protected function jsonSuccess($data = null): string
    {
        $response = [
            'success' => true,
            'data' => $data
        ];
        return json_encode($response);
    }

    protected function jsonError(string $message, int $code = 400): string
    {
        http_response_code($code);
        $response = [
            'success' => false,
            'error' => $message
        ];
        return json_encode($response);
    }

    protected function render(string $template, array $data = []): void
    {
        // Extract data to variables
        extract($data);

        // Start output buffering
        ob_start();

        // Include the template
        $templatePath = __DIR__ . '/../../templates/' . str_replace('/', '/', $template) . '.php';
        if (file_exists($templatePath)) {
            require $templatePath;
        } else {
            // Fallback to a simple message
            echo "<h1>Template not found: $template</h1>";
        }

        // Get the buffered content
        $content = ob_get_clean();

        // Render layout
        $this->renderLayout($content, [
            'title' => ucfirst(str_replace('/', ' ', $template)) . ' - Vite & Gourmand',
            'user' => $_SESSION['user_id'] ?? null,
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'role' => $_SESSION['role'] ?? '',
        ]);
    }

    protected function renderLayout(string $content, array $data = []): void
    {
        extract($data);

        // Include header
        $headerPath = __DIR__ . '/../../templates/layout/header.php';
        if (file_exists($headerPath)) {
            require $headerPath;
        }

        // Include flash messages
        $flashPath = __DIR__ . '/../../templates/layout/flash.php';
        if (file_exists($flashPath)) {
            require $flashPath;
        }

        // Main content
        echo $content;

        // Include footer
        $footerPath = __DIR__ . '/../../templates/layout/footer.php';
        if (file_exists($footerPath)) {
            require $footerPath;
        }
    }
}