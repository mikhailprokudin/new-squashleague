import type { MatchResult, MatchScore, Opponent, Player, PlayerStats, StandingTeam } from '@/types'

/** Static API token for POST / DELETE (must match back/config/auth.php). */
const API_TOKEN = '60a8a053-5c8f-4e25-b46b-d03ea0f70145'

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const method = (init?.method ?? 'GET').toUpperCase()
  const needsAuth = method === 'POST' || method === 'DELETE'

  const response = await fetch(path, {
    ...init,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(needsAuth ? { Authorization: API_TOKEN } : {}),
      ...(init?.headers ?? {}),
    },
  })

  const data = await response.json().catch(() => ({}))
  if (!response.ok) {
    const message = typeof data?.error === 'string' ? data.error : `Request failed (${response.status})`
    throw new Error(message)
  }

  return data as T
}

export function fetchStandings() {
  return request<{ teams: StandingTeam[] }>('/api/standings')
}

export function fetchPlayers() {
  return request<{ players: Player[] }>('/api/players')
}

export function fetchOpponents(playerId: number) {
  return request<{
    player: { id: number; name: string; team_id: number; division: string }
    opponents: Opponent[]
  }>(`/api/opponents?player_id=${playerId}`)
}

export function fetchMatches() {
  return request<{ matches: MatchResult[] }>('/api/matches')
}

export function fetchPlayerStats(playerId: number) {
  return request<PlayerStats>(`/api/players/${playerId}/stats`)
}

export function createMatch(payload: {
  player1_id: number
  player2_id: number
  score: MatchScore
  winner_id?: number
}) {
  return request<{
    match: MatchResult
    scoring: { match_type: string; points_winner: number; points_loser: number }
  }>('/api/matches', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function deleteMatch(id: number) {
  return request<{ ok: boolean }>(`/api/matches/${id}`, {
    method: 'DELETE',
  })
}
