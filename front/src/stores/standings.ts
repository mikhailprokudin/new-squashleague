import { defineStore } from 'pinia'
import { ref } from 'vue'
import { fetchStandings } from '@/api/client'
import type { StandingTeam } from '@/types'

export const useStandingsStore = defineStore('standings', () => {
  const teams = ref<StandingTeam[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function load() {
    loading.value = true
    error.value = null
    try {
      const data = await fetchStandings()
      teams.value = data.teams
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load standings'
    } finally {
      loading.value = false
    }
  }

  return { teams, loading, error, load }
})
