<?php

declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use App\Controllers\MatchesController;
use App\Controllers\PlayersController;
use App\Controllers\StandingsController;
use App\Response;

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rtrim($uri, '/') ?: '/';

// Support both /api/... and /index.php/api/... and bare /standings when docroot is public
if (str_starts_with($uri, '/index.php')) {
    $uri = substr($uri, strlen('/index.php')) ?: '/';
}

try {
    match (true) {
        $method === 'GET' && ($uri === '/api/standings' || $uri === '/standings')
            => (new StandingsController())->index(),

        $method === 'GET' && ($uri === '/api/players' || $uri === '/players')
            => (new PlayersController())->index(),

        $method === 'POST' && ($uri === '/api/players' || $uri === '/players')
            => (new PlayersController())->store(),

        $method === 'PUT' && preg_match('#^/api/players/(\d+)$#', $uri, $m)
            => (new PlayersController())->update((int) $m[1]),

        $method === 'PUT' && preg_match('#^/players/(\d+)$#', $uri, $m)
            => (new PlayersController())->update((int) $m[1]),

        $method === 'DELETE' && preg_match('#^/api/players/(\d+)$#', $uri, $m)
            => (new PlayersController())->destroy((int) $m[1]),

        $method === 'DELETE' && preg_match('#^/players/(\d+)$#', $uri, $m)
            => (new PlayersController())->destroy((int) $m[1]),

        $method === 'GET' && ($uri === '/api/opponents' || $uri === '/opponents')
            => (new MatchesController())->opponents(),

        $method === 'GET' && ($uri === '/api/matches' || $uri === '/matches')
            => (new MatchesController())->index(),

        $method === 'POST' && ($uri === '/api/matches' || $uri === '/matches')
            => (new MatchesController())->store(),

        default => Response::error('Not found', 404),
    };
} catch (Throwable $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
}
