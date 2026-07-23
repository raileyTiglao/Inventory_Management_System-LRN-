<?php
/**
 * API Response helpers — consistent JSON structure across all endpoints.
 * Usage: json_response(success: true, data: [...], status: 200);
 */

function json_response(bool $success, $data = null, string $message = '', int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'message' => $message,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    exit;
}

function json_error(string $message, int $status = 400): void
{
    json_response(false, null, $message, $status);
}

function json_success($data = null, string $message = '', int $status = 200): void
{
    json_response(true, $data, $message, $status);
}
