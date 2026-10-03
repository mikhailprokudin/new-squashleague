import { createRouter, createWebHistory } from 'vue-router'
import StandingsView from '@/views/StandingsView.vue'
import EntryView from '@/views/EntryView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'standings', component: StandingsView },
    { path: '/entry', name: 'entry', component: EntryView },
  ],
})

export default router
