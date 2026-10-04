<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'

const menu = ref<HTMLDetailsElement | null>(null)

function closeMenu() {
  if (menu.value) {
    menu.value.open = false
  }
}
</script>

<template>
  <div class="min-h-screen court-grid">
    <div class="navbar bg-neutral/80 text-neutral-content border-b border-base-300 backdrop-blur-sm px-4 lg:px-8">
      <div class="navbar-start">
        <RouterLink
          to="/"
          class="font-display text-xl tracking-[0.12em] text-primary hover:opacity-80"
        >
          Squash League
        </RouterLink>
      </div>
      <div class="navbar-end">
        <details ref="menu" class="dropdown dropdown-end">
          <summary class="btn btn-ghost btn-square" aria-label="Меню">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              class="inline-block h-5 w-5 stroke-current"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
          </summary>
          <ul
            class="menu dropdown-content bg-base-200 text-base-content rounded-box z-50 mt-3 w-56 border border-base-300 p-2 shadow-lg"
          >
            <li>
              <RouterLink to="/" @click="closeMenu">Таблица лиги</RouterLink>
            </li>
            <li>
              <RouterLink to="/results" @click="closeMenu">Результаты матчей</RouterLink>
            </li>
          </ul>
        </details>
      </div>
    </div>
    <main class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
      <RouterView />
    </main>
  </div>
</template>
