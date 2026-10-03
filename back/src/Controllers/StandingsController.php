<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Repositories\StandingsRepository;
use App\Response;

final class StandingsController
{
    public function index(): void
    {
        $repo = new StandingsRepository(Database::connection());
        Response::json(['teams' => $repo->getStandings()]);
    }
}
