<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useMatchEntryStore } from '@/stores/matchEntry'
import { resolveMatchResult, type MatchScore } from '@/types'

const store = useMatchEntryStore()
const {
  players,
  opponents,
  loadingPlayers,
  loadingOpponents,
  submitting,
  error,
  success,
} = storeToRefs(store)

const player1Id = ref<number | null>(null)
const player2Id = ref<number | null>(null)
const score = ref<MatchScore | ''>('')

const scores: MatchScore[] = ['3-0', '3-1', '3-2', '0-3', '1-3', '2-3']

onMounted(() => {
  void store.loadPlayers()
})

watch(player1Id, (id) => {
  player2Id.value = null
  score.value = ''
  store.clearOpponents()
  if (id) {
    void store.loadOpponents(id)
  }
})

watch(player2Id, () => {
  score.value = ''
})

const player1 = computed(() => players.value.find((p) => p.id === player1Id.value) ?? null)
const player2 = computed(() => opponents.value.find((p) => p.id === player2Id.value) ?? null)

const canSubmit = computed(
  () =>
    player1Id.value !== null &&
    player2Id.value !== null &&
    score.value !== '' &&
    !submitting.value,
)

async function onSubmit() {
  if (
    !canSubmit.value ||
    player1Id.value === null ||
    player2Id.value === null ||
    score.value === ''
  ) {
    return
  }

  const resolved = resolveMatchResult(player1Id.value, player2Id.value, score.value)

  try {
    await store.submit({
      player1_id: player1Id.value,
      player2_id: player2Id.value,
      winner_id: resolved.winner_id,
      score: resolved.score,
    })
    player1Id.value = null
    player2Id.value = null
    score.value = ''
    store.clearOpponents()
  } catch {
    // error already in store
  }
}

function labelForPlayer(p: { name: string; team_name?: string | null; division: string }) {
  const team = p.team_name ? `${p.team_name} · ` : ''
  const div = p.division === 'red' ? 'красный' : 'жёлтый'
  return `${team}${p.name} (${div})`
}

function scoreLabel(s: MatchScore) {
  if (!player1.value || !player2.value) {
    return s
  }
  return `${s} — ${s.startsWith('3') ? player1.value.name : player2.value.name}`
}
</script>

<template>
  <div class="mx-auto flex max-w-xl flex-col gap-6">
    <header class="flex flex-col gap-2">
      <p class="text-sm font-semibold tracking-[0.2em] text-primary uppercase">Match desk</p>
      <h1 class="font-display text-4xl text-base-content">Ввод результата</h1>
      <p class="text-base-content/70">
        Счёт от первого игрока: 3-x — победа первого, x-3 — победа соперника.
      </p>
    </header>

    <form class="card card-border bg-base-200 shadow-md" @submit.prevent="onSubmit">
      <div class="card-body gap-4">
        <fieldset class="fieldset">
          <legend class="fieldset-legend">Игрок 1</legend>
          <select v-model.number="player1Id" class="select w-full" :disabled="loadingPlayers">
            <option :value="null" disabled>Выберите игрока</option>
            <option v-for="p in players" :key="p.id" :value="p.id">
              {{ labelForPlayer(p) }}
            </option>
          </select>
        </fieldset>

        <fieldset class="fieldset">
          <legend class="fieldset-legend">Соперник</legend>
          <select
            v-model.number="player2Id"
            class="select w-full"
            :disabled="!player1Id || loadingOpponents"
          >
            <option :value="null" disabled>
              {{ loadingOpponents ? 'Загрузка…' : 'Выберите соперника' }}
            </option>
            <option v-for="o in opponents" :key="o.id" :value="o.id">
              {{ labelForPlayer(o) }} — осталось матчей: {{ o.matches_remaining }}
            </option>
          </select>
          <p
            v-if="player1Id && !loadingOpponents && opponents.length === 0"
            class="label text-warning"
          >
            Нет доступных соперников (лимиты матчей исчерпаны или нет игроков других команд).
          </p>
        </fieldset>

        <fieldset class="fieldset">
          <legend class="fieldset-legend">Счёт</legend>
          <select v-model="score" class="select w-full" :disabled="!player1 || !player2">
            <option value="" disabled>Выберите счёт</option>
            <option v-for="s in scores" :key="s" :value="s">{{ scoreLabel(s) }}</option>
          </select>
        </fieldset>

        <div class="card-actions mt-2">
          <button class="btn btn-primary btn-block" type="submit" :disabled="!canSubmit">
            <span v-if="submitting" class="loading loading-spinner"></span>
            {{ submitting ? 'Сохранение…' : 'Сохранить результат' }}
          </button>
        </div>

        <div v-if="success" role="alert" class="alert alert-success alert-soft">
          <span>{{ success }}</span>
        </div>
        <div v-if="error" role="alert" class="alert alert-error alert-soft">
          <span>{{ error }}</span>
        </div>
      </div>
    </form>
  </div>
</template>
