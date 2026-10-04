import { defineStore } from 'pinia'
import { ref } from 'vue'
import { fetchPlayerStats } from '@/api/client'
import type { PlayerStats } from '@/types'

export const usePlayerStatsStore = defineStore('playerStats', () => {
  const stats = ref<PlayerStats | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function load(playerId: number) {
    loading.value = true
    error.value = null
    stats.value = null
    try {
      stats.value = await fetchPlayerStats(playerId)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load player stats'
    } finally {
      loading.value = false
    }
  }

  return { stats, loading, error, load }
})
