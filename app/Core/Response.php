<?php

namespace App\Core;

class Response
{
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    public function json(array $data, int $status = 200): void
    {
        $this->setStatusCode($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}
