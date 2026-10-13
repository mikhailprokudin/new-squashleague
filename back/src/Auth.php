<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    public static function requireToken(): void
    {
        $config = require dirname(__DIR__) . '/config/auth.php';
        $expected = (string) ($config['token'] ?? '');
        $provided = self::authorizationHeader();

        if ($expected === '' || $provided === null || !hash_equals($expected, $provided)) {
            Response::error('Unauthorized', 401);
            exit;
        }
    }

    private static function authorizationHeader(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

        if (($header === null || $header === '') && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp((string) $name, 'Authorization') === 0) {
                    $header = $value;
                    break;
                }
            }
        }

        if (!is_string($header) || $header === '') {
            return null;
        }

        $header = trim($header);
        if (str_starts_with(strtolower($header), 'bearer ')) {
            $header = trim(substr($header, 7));
        }

        return $header === '' ? null : $header;
    }
}
