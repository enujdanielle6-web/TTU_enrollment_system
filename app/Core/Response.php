<?php

namespace App\Core;

class Response
{
    public ?string $redirectUrl = null;
    public int $statusCode = 200;
    public ?array $jsonData = null;

    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
        if (!defined('TESTING_ENV') || !TESTING_ENV) {
            http_response_code($code);
        }
    }

    public function json(array $data, int $status = 200): void
    {
        $this->jsonData = $data;
        $this->setStatusCode($status);
        if (!defined('TESTING_ENV') || !TESTING_ENV) {
            header('Content-Type: application/json');
            echo json_encode($data);
        }
    }

    public function redirect(string $url): void
    {
        $this->redirectUrl = $url;
        if (defined('TESTING_ENV') && TESTING_ENV) {
            return;
        }
        header("Location: $url");
        exit;
    }
}
