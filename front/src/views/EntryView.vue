<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useMatchEntryStore } from '@/stores/matchEntry'
import type { MatchScore } from '@/types'
import PlayerCombobox, { type ComboboxOption } from '@/components/PlayerCombobox.vue'

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

const playerOptions = computed<ComboboxOption[]>(() =>
  players.value.map((p) => ({
    id: p.id,
    label: labelForPlayer(p),
  })),
)

const opponentOptions = computed<ComboboxOption[]>(() =>
  opponents.value.map((o) => ({
    id: o.id,
    label: `${labelForPlayer(o)} — осталось матчей: ${o.matches_remaining}`,
  })),
)

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

  try {
    // Score is always from player1's perspective (3-x or x-3). Backend derives the winner.
    await store.submit({
      player1_id: player1Id.value,
      player2_id: player2Id.value,
      score: score.value,
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
          <PlayerCombobox
            v-model="player1Id"
            :options="playerOptions"
            :disabled="loadingPlayers"
            placeholder="Начните вводить имя игрока…"
          />
        </fieldset>

        <fieldset class="fieldset">
          <legend class="fieldset-legend">Соперник</legend>
          <PlayerCombobox
            v-model="player2Id"
            :options="opponentOptions"
            :disabled="!player1Id || loadingOpponents"
            :placeholder="loadingOpponents ? 'Загрузка…' : 'Начните вводить имя соперника…'"
          />
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
