<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useMatchesStore } from '@/stores/matches'
import { displayScoreFromPlayer1, type MatchResult } from '@/types'

const store = useMatchesStore()
const { matches, loading, error } = storeToRefs(store)

onMounted(() => {
  void store.load()
})

const isEmpty = computed(() => !loading.value && !error.value && matches.value.length === 0)

function formatDate(value?: string) {
  if (!value) {
    return '—'
  }
  const date = new Date(value.includes('T') ? value : value.replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) {
    return value
  }
  return new Intl.DateTimeFormat('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

function playerCell(name?: string, team?: string) {
  return { name: name ?? '—', team: team ?? '' }
}

function scoreClass(match: MatchResult) {
  return match.winner_id === match.player1_id ? 'text-success' : 'text-warning'
}
</script>

<template>
  <div class="flex flex-col gap-8">
    <header class="flex flex-col gap-2">
      <p class="text-sm font-semibold tracking-[0.2em] text-primary uppercase">Match log</p>
      <h1 class="font-display text-4xl md:text-5xl text-base-content">Результаты матчей</h1>
    </header>

    <div v-if="loading" class="flex items-center gap-3 text-base-content/70">
      <span class="loading loading-spinner loading-md text-primary"></span>
      Загрузка результатов…
    </div>

    <div v-else-if="error" role="alert" class="alert alert-error alert-soft">
      <span>{{ error }}</span>
    </div>

    <div v-else-if="isEmpty" role="alert" class="alert alert-info alert-soft">
      <span>Пока нет сыгранных матчей.</span>
    </div>

    <div v-else class="card card-border bg-base-200 shadow-md">
      <div class="card-body p-0 sm:p-2">
        <div class="overflow-x-auto">
          <table class="table table-zebra table-sm md:table-md">
            <thead>
              <tr>
                <th>Дата</th>
                <th>Игрок 1</th>
                <th>Игрок 2</th>
                <th>Счёт</th>
                <th>Победитель</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="match in matches" :key="match.id">
                <td class="whitespace-nowrap text-base-content/80">
                  {{ formatDate(match.created_at) }}
                </td>
                <td>
                  <div class="font-semibold">
                    {{ playerCell(match.player1_name, match.player1_team_name).name }}
                  </div>
                  <div class="text-xs text-base-content/60">
                    {{ playerCell(match.player1_name, match.player1_team_name).team }}
                  </div>
                </td>
                <td>
                  <div class="font-semibold">
                    {{ playerCell(match.player2_name, match.player2_team_name).name }}
                  </div>
                  <div class="text-xs text-base-content/60">
                    {{ playerCell(match.player2_name, match.player2_team_name).team }}
                  </div>
                </td>
                <td class="font-bold tabular-nums" :class="scoreClass(match)">
                  {{ displayScoreFromPlayer1(match) }}
                </td>
                <td class="font-semibold text-primary">{{ match.winner_name }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
