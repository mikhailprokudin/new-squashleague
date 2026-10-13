<script setup lang="ts">
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useMatchEntryStore } from '@/stores/matchEntry'
import { useMatchesStore } from '@/stores/matches'
import { displayScoreFromPlayer1, type MatchResult, type MatchScore } from '@/types'
import PlayerCombobox, { type ComboboxOption } from '@/components/PlayerCombobox.vue'

const store = useMatchEntryStore()
const matchesStore = useMatchesStore()
const {
  players,
  opponents,
  loadingPlayers,
  loadingOpponents,
  submitting,
  error,
  success,
} = storeToRefs(store)
const {
  matches,
  loading: loadingMatches,
  deletingId,
  error: matchesError,
} = storeToRefs(matchesStore)

const player1Id = ref<number | null>(null)
const player2Id = ref<number | null>(null)
const score = ref<MatchScore | ''>('')
const pendingMatch = ref<MatchResult | null>(null)
const deleteDialog = useTemplateRef<HTMLDialogElement>('deleteDialog')

const scores: MatchScore[] = ['3-0', '3-1', '3-2', '0-3', '1-3', '2-3']

onMounted(() => {
  void store.loadPlayers()
  void matchesStore.load()
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

const isMatchesEmpty = computed(
  () => !loadingMatches.value && !matchesError.value && matches.value.length === 0,
)

const isDeleting = computed(
  () => pendingMatch.value !== null && deletingId.value === pendingMatch.value.id,
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
    void matchesStore.load()
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

function openDeleteDialog(match: MatchResult) {
  pendingMatch.value = match
  deleteDialog.value?.showModal()
}

function closeDeleteDialog() {
  deleteDialog.value?.close()
  pendingMatch.value = null
}

async function confirmDelete() {
  if (!pendingMatch.value || isDeleting.value) {
    return
  }

  try {
    await matchesStore.remove(pendingMatch.value.id)
    closeDeleteDialog()
    if (player1Id.value) {
      void store.loadOpponents(player1Id.value)
    }
  } catch {
    // error is already stored in the matches store
  }
}
</script>

<template>
  <div class="mx-auto flex w-full max-w-4xl flex-col gap-8">
    <header class="flex flex-col gap-2">
      <p class="text-sm font-semibold tracking-[0.2em] text-primary uppercase">Match desk</p>
      <h1 class="font-display text-4xl text-base-content">Ввод результата</h1>
      <p class="text-base-content/70">
        Счёт от первого игрока: 3-x — победа первого, x-3 — победа соперника.
      </p>
    </header>

    <form class="card card-border mx-auto w-full max-w-xl bg-base-200 shadow-md" @submit.prevent="onSubmit">
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

    <section class="flex flex-col gap-4">
      <header class="flex flex-col gap-1">
        <h2 class="font-display text-2xl text-base-content">Сыгранные матчи</h2>
        <p class="text-sm text-base-content/70">Можно удалить матч, если результат внесён ошибочно.</p>
      </header>

      <div v-if="loadingMatches" class="flex items-center gap-3 text-base-content/70">
        <span class="loading loading-spinner loading-md text-primary"></span>
        Загрузка матчей…
      </div>

      <div
        v-else-if="matchesError && matches.length === 0"
        role="alert"
        class="alert alert-error alert-soft"
      >
        <span>{{ matchesError }}</span>
      </div>

      <div v-else-if="isMatchesEmpty" role="alert" class="alert alert-info alert-soft">
        <span>Пока нет сыгранных матчей.</span>
      </div>

      <template v-else>
        <div v-if="matchesError" role="alert" class="alert alert-error alert-soft">
          <span>{{ matchesError }}</span>
        </div>

        <div class="card card-border bg-base-200 shadow-md">
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
                    <th class="w-16"></th>
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
                    <td>
                      <button
                        type="button"
                        class="btn btn-ghost btn-xs btn-square text-error"
                        :disabled="deletingId === match.id"
                        :aria-label="`Удалить матч ${match.player1_name} — ${match.player2_name}`"
                        @click="openDeleteDialog(match)"
                      >
                        <span
                          v-if="deletingId === match.id"
                          class="loading loading-spinner loading-xs"
                        ></span>
                        <svg
                          v-else
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          class="size-4"
                          aria-hidden="true"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193v-.443A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z"
                            clip-rule="evenodd"
                          />
                        </svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </template>
    </section>

    <dialog ref="deleteDialog" class="modal" @close="pendingMatch = null">
      <div class="modal-box">
        <h3 class="font-bold text-lg">Удалить матч?</h3>
        <p v-if="pendingMatch" class="py-4 text-base-content/80">
          {{ pendingMatch.player1_name }} — {{ pendingMatch.player2_name }}
          ({{ displayScoreFromPlayer1(pendingMatch) }}). Очки в таблице пересчитаются.
        </p>
        <div class="modal-action">
          <button type="button" class="btn" :disabled="isDeleting" @click="closeDeleteDialog">
            Отмена
          </button>
          <button
            type="button"
            class="btn btn-error"
            :disabled="isDeleting"
            @click="confirmDelete"
          >
            <span v-if="isDeleting" class="loading loading-spinner loading-sm"></span>
            Удалить
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button type="submit" :disabled="isDeleting">close</button>
      </form>
    </dialog>
  </div>
</template>
