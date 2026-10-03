<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useMatchEntryStore } from '@/stores/matchEntry'
import type { Score } from '@/types'

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
const winnerId = ref<number | null>(null)
const score = ref<Score | ''>('')

const scores: Score[] = ['3-0', '3-1', '3-2']

onMounted(() => {
  void store.loadPlayers()
})

watch(player1Id, (id) => {
  player2Id.value = null
  winnerId.value = null
  score.value = ''
  store.clearOpponents()
  if (id) {
    void store.loadOpponents(id)
  }
})

watch(player2Id, () => {
  winnerId.value = null
})

const player1 = computed(() => players.value.find((p) => p.id === player1Id.value) ?? null)
const player2 = computed(() => opponents.value.find((p) => p.id === player2Id.value) ?? null)

const canSubmit = computed(
  () =>
    player1Id.value !== null &&
    player2Id.value !== null &&
    winnerId.value !== null &&
    score.value !== '' &&
    !submitting.value,
)

async function onSubmit() {
  if (
    !canSubmit.value ||
    player1Id.value === null ||
    player2Id.value === null ||
    winnerId.value === null ||
    score.value === ''
  ) {
    return
  }

  try {
    await store.submit({
      player1_id: player1Id.value,
      player2_id: player2Id.value,
      winner_id: winnerId.value,
      score: score.value,
    })
    player1Id.value = null
    player2Id.value = null
    winnerId.value = null
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
</script>

<template>
  <div class="mx-auto flex max-w-xl flex-col gap-6">
    <header class="flex flex-col gap-2">
      <p class="text-sm font-semibold tracking-[0.2em] text-primary uppercase">Match desk</p>
      <h1 class="font-display text-4xl text-base-content">Ввод результата</h1>
      <p class="text-base-content/70">Зафиксируйте счёт — очки начислятся сразу после сохранения.</p>
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

        <fieldset class="fieldset" :disabled="!player1 || !player2">
          <legend class="fieldset-legend">Победитель</legend>
          <div class="flex flex-col gap-3">
            <label class="flex cursor-pointer items-center gap-3">
              <input
                v-model.number="winnerId"
                type="radio"
                name="winner"
                class="radio radio-primary"
                :value="player1?.id"
              />
              <span>{{ player1?.name ?? 'Игрок 1' }}</span>
            </label>
            <label class="flex cursor-pointer items-center gap-3">
              <input
                v-model.number="winnerId"
                type="radio"
                name="winner"
                class="radio radio-primary"
                :value="player2?.id"
              />
              <span>{{ player2?.name ?? 'Соперник' }}</span>
            </label>
          </div>
        </fieldset>

        <fieldset class="fieldset">
          <legend class="fieldset-legend">Счёт</legend>
          <select v-model="score" class="select w-full" :disabled="!winnerId">
            <option value="" disabled>Выберите счёт</option>
            <option v-for="s in scores" :key="s" :value="s">{{ s }}</option>
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
