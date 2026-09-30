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

    protected function redirect(string $url): void
    {
        $response = new Response();
        $response->redirect($url);
        exit;
    }

    protected function notFound(?Response $response = null, string $message = '404 Not Found - The requested resource does not exist.'): void
    {
        $res = $response ?? new Response();
        $res->setStatusCode(404);
        echo $message;
        exit;
    }

    protected function forbidden(?Response $response = null, string $message = '403 Forbidden - You are not authorized to perform this action.'): void
    {
        $res = $response ?? new Response();
        $res->setStatusCode(403);
        echo $message;
        exit;
    }
}
