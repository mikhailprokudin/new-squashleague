<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class ScoringService
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require dirname(__DIR__) . '/config/scoring.php';
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function maxMatchesForPair(string $division1, string $division2): int
    {
        if ($division1 === $division2) {
            return (int) $this->config['max_matches_same_division'];
        }

        return (int) $this->config['max_matches_cross_division'];
    }

    /**
     * Maximum points a player can earn from one match: win 3-0 vs opponent's division.
     */
    public function maxWinnerPointsForMatch(string $playerDivision, string $opponentDivision): float
    {
        $result = $this->calculate($playerDivision, $opponentDivision, '3-0');

        return $result['winner'];
    }

    /**
     * @return array{winner: float, loser: float, match_type: string}
     */
    public function calculate(string $winnerDivision, string $loserDivision, string $score): array
    {
        $allowed = $this->config['allowed_scores'];
        if (!in_array($score, $allowed, true)) {
            throw new InvalidArgumentException('Invalid score. Allowed: ' . implode(', ', $allowed));
        }

        $matchType = $this->resolveMatchType($winnerDivision, $loserDivision);
        $points = $this->config[$matchType][$score] ?? null;

        if ($points === null) {
            throw new InvalidArgumentException("No scoring rule for {$matchType} / {$score}");
        }

        return [
            'winner' => (float) $points['winner'],
            'loser' => (float) $points['loser'],
            'match_type' => $matchType,
        ];
    }

    private function resolveMatchType(string $winnerDivision, string $loserDivision): string
    {
        if ($winnerDivision === $loserDivision) {
            return 'same_division';
        }

        $higher = $this->config['higher_division'];
        $lower = $this->config['lower_division'];

        if ($winnerDivision === $higher && $loserDivision === $lower) {
            return 'higher_vs_lower';
        }

        if ($winnerDivision === $lower && $loserDivision === $higher) {
            return 'lower_vs_higher';
        }

        throw new InvalidArgumentException('Unknown division pair');
    }
}
