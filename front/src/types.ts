export type Division = 'red' | 'yellow'

/** Score from player1 perspective: 3-x = player1 won, x-3 = player2 won */
export type MatchScore = '3-0' | '3-1' | '3-2' | '0-3' | '1-3' | '2-3'

/** Normalized score stored in API/DB (always from winner's side) */
export type Score = '3-0' | '3-1' | '3-2'

export interface StandingPlayer {
  id: number
  name: string
  division: Division
  points: number
  matches_played: number
}

export interface StandingTeam {
  id: number
  name: string
  slug: string
  players: StandingPlayer[]
}

export interface Player {
  id: number
  team_id: number
  team_name: string | null
  team_slug: string | null
  division: Division
  name: string
  is_active: boolean
}

export interface Opponent {
  id: number
  name: string
  team_id: number
  team_name: string
  team_slug: string
  division: Division
  matches_played: number
  matches_remaining: number
}

export interface MatchResult {
  id: number
  player1_id: number
  player2_id: number
  winner_id: number
  score: Score
  points_player1: number
  points_player2: number
  player1_name?: string
  player2_name?: string
  winner_name?: string
}

export function resolveMatchResult(
  player1Id: number,
  player2Id: number,
  matchScore: MatchScore,
): { winner_id: number; score: Score } {
  const map: Record<MatchScore, { winnerIsPlayer1: boolean; score: Score }> = {
    '3-0': { winnerIsPlayer1: true, score: '3-0' },
    '3-1': { winnerIsPlayer1: true, score: '3-1' },
    '3-2': { winnerIsPlayer1: true, score: '3-2' },
    '0-3': { winnerIsPlayer1: false, score: '3-0' },
    '1-3': { winnerIsPlayer1: false, score: '3-1' },
    '2-3': { winnerIsPlayer1: false, score: '3-2' },
  }

  const resolved = map[matchScore]
  return {
    winner_id: resolved.winnerIsPlayer1 ? player1Id : player2Id,
    score: resolved.score,
  }
}
