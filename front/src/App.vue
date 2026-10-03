<script setup lang="ts">
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="min-h-screen court-grid">
    <div class="navbar bg-neutral/80 text-neutral-content border-b border-base-300 backdrop-blur-sm px-4 lg:px-8">
      <div class="navbar-start">
        <span class="font-display text-xl tracking-[0.12em] text-primary">Squash League</span>
      </div>
      <div class="navbar-end">
        <span class="badge badge-outline badge-sm tracking-wide">Season table</span>
      </div>
    </div>
    <main class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
      <RouterView />
    </main>
  </div>
</template>
