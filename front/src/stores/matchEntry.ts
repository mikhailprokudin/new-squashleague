import { defineStore } from 'pinia'
import { ref } from 'vue'
import { createMatch, fetchOpponents, fetchPlayers } from '@/api/client'
import type { Opponent, Player, Score } from '@/types'

export const useMatchEntryStore = defineStore('matchEntry', () => {
  const players = ref<Player[]>([])
  const opponents = ref<Opponent[]>([])
  const loadingPlayers = ref(false)
  const loadingOpponents = ref(false)
  const submitting = ref(false)
  const error = ref<string | null>(null)
  const success = ref<string | null>(null)

  async function loadPlayers() {
    loadingPlayers.value = true
    error.value = null
    try {
      const data = await fetchPlayers()
      players.value = data.players
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load players'
    } finally {
      loadingPlayers.value = false
    }
  }

  async function loadOpponents(playerId: number) {
    loadingOpponents.value = true
    opponents.value = []
    error.value = null
    try {
      const data = await fetchOpponents(playerId)
      opponents.value = data.opponents
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load opponents'
    } finally {
      loadingOpponents.value = false
    }
  }

  function clearOpponents() {
    opponents.value = []
  }

  async function submit(payload: {
    player1_id: number
    player2_id: number
    winner_id: number
    score: Score
  }) {
    submitting.value = true
    error.value = null
    success.value = null
    try {
      const result = await createMatch(payload)
      const { scoring, match } = result
      success.value =
        `Матч сохранён: ${match.winner_name ?? 'победитель'} ${match.score}. ` +
        `Очки: +${scoring.points_winner} / +${scoring.points_loser}`
      return result
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to save match'
      throw e
    } finally {
      submitting.value = false
    }
  }

  return {
    players,
    opponents,
    loadingPlayers,
    loadingOpponents,
    submitting,
    error,
    success,
    loadPlayers,
    loadOpponents,
    clearOpponents,
    submit,
  }
})
