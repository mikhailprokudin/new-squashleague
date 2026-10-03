export type Division = 'red' | 'yellow'
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
