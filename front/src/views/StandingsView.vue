<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useStandingsStore } from '@/stores/standings'
import type { StandingPlayer } from '@/types'
import { formatPointsLabel } from '@/utils/pluralize'

const store = useStandingsStore()
const router = useRouter()
const { teams, loading, error } = storeToRefs(store)

onMounted(() => {
  void store.load()
})

function byDivision(players: StandingPlayer[], division: 'red' | 'yellow') {
  return players.filter((p) => p.division === division)
}

function formatPoints(value: number) {
  return Number.isInteger(value) ? String(value) : value.toFixed(1)
}

function teamTotalPoints(players: StandingPlayer[]) {
  return players.reduce((sum, player) => sum + player.points, 0)
}

function openPlayer(playerId: number) {
  void router.push({ name: 'player', params: { id: String(playerId) } })
}

const hasTeams = computed(() => teams.value.length > 0)
</script>

<template>
  <div class="flex flex-col gap-8">
    <header class="flex flex-col gap-2">
      <p class="text-sm font-semibold tracking-[0.2em] text-primary uppercase">Court standings</p>
      <h1 class="font-display text-4xl md:text-5xl text-base-content">Таблица лиги</h1>
    </header>

    <div v-if="loading" class="flex items-center gap-3 text-base-content/70">
      <span class="loading loading-spinner loading-md text-primary"></span>
      Загрузка таблицы…
    </div>

    <div v-else-if="error" role="alert" class="alert alert-error alert-soft">
      <span>{{ error }}</span>
    </div>

    <div v-else-if="hasTeams" class="grid grid-cols-1 gap-5 lg:grid-cols-3">
      <section
        v-for="team in teams"
        :key="team.id"
        class="card card-border bg-base-200 shadow-md"
      >
        <div class="card-body gap-5">
          <div class="flex items-center justify-between gap-3">
            <h2 class="card-title font-display text-2xl tracking-wide">{{ team.name }}</h2>
            <span class="badge badge-secondary badge-outline tabular-nums">
              {{ formatPointsLabel(teamTotalPoints(team.players)) }}
            </span>
          </div>

          <div class="rounded-box border border-error/40 bg-error/10 p-3">
            <div class="mb-2 flex items-center justify-between">
              <h3 class="font-display text-sm tracking-[0.15em] text-error">Красный</h3>
              <span class="badge badge-error badge-sm">Red</span>
            </div>
            <div class="overflow-x-auto">
              <table class="table table-sm md:table-md table-zebra">
                <thead>
                  <tr>
                    <th>Игрок</th>
                    <th class="text-right">Очки</th>
                    <th class="text-right">Матчи</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="player in byDivision(team.players, 'red')"
                    :key="player.id"
                    class="hover:bg-base-300/40 cursor-pointer"
                    @click="openPlayer(player.id)"
                  >
                    <td class="font-semibold text-sm md:text-base">{{ player.name }}</td>
                    <td class="text-right tabular-nums text-primary font-bold text-sm md:text-base">
                      {{ formatPoints(player.points) }}
                    </td>
                    <td class="text-right tabular-nums text-sm md:text-base">{{ player.matches_played }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="rounded-box border border-warning/40 bg-warning/10 p-3">
            <div class="mb-2 flex items-center justify-between">
              <h3 class="font-display text-sm tracking-[0.15em] text-warning">Жёлтый</h3>
              <span class="badge badge-warning badge-sm">Yellow</span>
            </div>
            <div class="overflow-x-auto">
              <table class="table table-sm md:table-md table-zebra">
                <thead>
                  <tr>
                    <th>Игрок</th>
                    <th class="text-right">Очки</th>
                    <th class="text-right">Матчи</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="player in byDivision(team.players, 'yellow')"
                    :key="player.id"
                    class="hover:bg-base-300/40 cursor-pointer"
                    @click="openPlayer(player.id)"
                  >
                    <td class="font-semibold text-sm md:text-base">{{ player.name }}</td>
                    <td class="text-right tabular-nums text-primary font-bold text-sm md:text-base">
                      {{ formatPoints(player.points) }}
                    </td>
                    <td class="text-right tabular-nums text-sm md:text-base">{{ player.matches_played }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
