import { defineStore } from 'pinia'
import { ref } from 'vue'
import { deleteMatch, fetchMatches } from '@/api/client'
import type { MatchResult } from '@/types'

export const useMatchesStore = defineStore('matches', () => {
  const matches = ref<MatchResult[]>([])
  const loading = ref(false)
  const deletingId = ref<number | null>(null)
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

  async function remove(id: number) {
    deletingId.value = id
    error.value = null
    try {
      await deleteMatch(id)
      matches.value = matches.value.filter((match) => match.id !== id)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete match'
      throw e
    } finally {
      deletingId.value = null
    }
  }

  return { matches, loading, deletingId, error, load, remove }
})
