<script setup lang="ts">
import { computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, RouterLink } from 'vue-router'
import { usePlayerStatsStore } from '@/stores/playerStats'

const route = useRoute()
const store = usePlayerStatsStore()
const { stats, loading, error } = storeToRefs(store)

const playerId = computed(() => Number(route.params.id))

watch(
  playerId,
  (id) => {
    if (Number.isFinite(id) && id > 0) {
      void store.load(id)
    }
  },
  { immediate: true },
)

function formatPoints(value: number) {
  return Number.isInteger(value) ? String(value) : value.toFixed(1)
}

function formatDate(value?: string | null) {
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

function divisionLabel(division: string) {
  return division === 'red' ? 'красный' : 'жёлтый'
}
</script>

<template>
  <div class="flex flex-col gap-8">
    <div>
      <RouterLink to="/" class="link link-hover text-sm text-base-content/70">
        ← К таблице лиги
      </RouterLink>
    </div>

    <div v-if="loading" class="flex items-center gap-3 text-base-content/70">
      <span class="loading loading-spinner loading-md text-primary"></span>
      Загрузка статистики…
    </div>

    <div v-else-if="error" role="alert" class="alert alert-error alert-soft">
      <span>{{ error }}</span>
    </div>

    <template v-else-if="stats">
      <header class="flex flex-col gap-3">
        <p class="text-sm font-semibold tracking-[0.2em] text-primary uppercase">Player card</p>
        <h1 class="font-display text-4xl md:text-5xl text-base-content">
          {{ stats.player.name }}
        </h1>
        <div class="flex flex-wrap gap-2">
          <span class="badge badge-outline">{{ stats.player.team_name }}</span>
          <span
            class="badge"
            :class="stats.player.division === 'red' ? 'badge-error' : 'badge-warning'"
          >
            {{ divisionLabel(stats.player.division) }}
          </span>
        </div>
      </header>

      <div class="stats stats-vertical lg:stats-horizontal bg-base-200 border border-base-300 shadow-md w-full">
        <div class="stat">
          <div class="stat-title">Очки</div>
          <div class="stat-value text-primary">{{ formatPoints(stats.points) }}</div>
          <div class="stat-desc">набрано в матчах</div>
        </div>
        <div class="stat">
          <div class="stat-title">Игры</div>
          <div class="stat-value">{{ stats.matches_played }}</div>
          <div class="stat-desc">сыграно матчей</div>
        </div>
        <div class="stat">
          <div class="stat-title">Полезность</div>
          <div class="stat-value text-secondary">
            {{ stats.usefulness === null ? '—' : `${stats.usefulness}%` }}
          </div>
          <div class="stat-desc">
            от макс. {{ formatPoints(stats.max_possible_points) }} при победах 3-0
          </div>
        </div>
      </div>

      <section class="card card-border bg-base-200 shadow-md">
        <div class="card-body gap-4">
          <h2 class="card-title font-display tracking-wide">Сыгранные матчи</h2>

          <div
            v-if="stats.played_matches.length === 0"
            role="alert"
            class="alert alert-info alert-soft"
          >
            <span>Ещё нет сыгранных матчей.</span>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="table table-zebra table-sm md:table-md">
              <thead>
                <tr>
                  <th>Дата</th>
                  <th>Соперник</th>
                  <th>Счёт</th>
                  <th class="text-right">Очки</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="match in stats.played_matches" :key="match.id">
                  <td class="whitespace-nowrap text-base-content/80">
                    {{ formatDate(match.created_at) }}
                  </td>
                  <td>
                    <div class="font-semibold">{{ match.opponent_name }}</div>
                    <div class="text-xs text-base-content/60">
                      {{ match.opponent_team_name }} · {{ divisionLabel(match.opponent_division) }}
                    </div>
                  </td>
                  <td
                    class="font-bold tabular-nums"
                    :class="match.won ? 'text-success' : 'text-warning'"
                  >
                    {{ match.score }}
                    <span class="badge badge-ghost badge-sm ml-1">
                      {{ match.won ? 'победа' : 'поражение' }}
                    </span>
                  </td>
                  <td class="text-right tabular-nums font-semibold text-primary">
                    {{ formatPoints(match.points_earned) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section class="card card-border bg-base-200 shadow-md">
        <div class="card-body gap-4">
          <div class="flex items-center justify-between gap-3">
            <h2 class="card-title font-display tracking-wide">Несыгранные матчи</h2>
            <span class="badge badge-outline">{{ stats.unplayed_matches.length }}</span>
          </div>

          <div
            v-if="stats.unplayed_matches.length === 0"
            role="alert"
            class="alert alert-success alert-soft"
          >
            <span>Все положенные матчи сыграны.</span>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="table table-zebra table-sm md:table-md">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Соперник</th>
                  <th>Команда</th>
                  <th>Дивизион</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(match, index) in stats.unplayed_matches"
                  :key="`${match.opponent_id}-${index}`"
                >
                  <td class="tabular-nums text-base-content/60">{{ index + 1 }}</td>
                  <td class="font-semibold">{{ match.opponent_name }}</td>
                  <td>{{ match.opponent_team_name }}</td>
                  <td>
                    <span
                      class="badge badge-sm"
                      :class="match.opponent_division === 'red' ? 'badge-error' : 'badge-warning'"
                    >
                      {{ divisionLabel(match.opponent_division) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
