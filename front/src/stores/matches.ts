import { defineStore } from 'pinia'
import { ref } from 'vue'
import { fetchMatches } from '@/api/client'
import type { MatchResult } from '@/types'

export const useMatchesStore = defineStore('matches', () => {
  const matches = ref<MatchResult[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function load() {
    loading.value = true
    error.value = null
    try {
      const data = await fetchMatches()
      matches.value = data.matches
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load matches'
    } finally {
      loading.value = false
    }
  }

  return { matches, loading, error, load }
})
