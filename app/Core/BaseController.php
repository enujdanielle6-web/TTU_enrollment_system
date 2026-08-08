<?php

namespace App\Core;

class BaseController
{
    protected function render(string $view, array $params = []): string
    {
        extract($params);
        ob_start();
        include_once __DIR__ . "/../Views/$view.php";
        return ob_get_clean();
    }
}
