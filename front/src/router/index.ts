import { createRouter, createWebHistory } from 'vue-router'
import StandingsView from '@/views/StandingsView.vue'
import EntryView from '@/views/EntryView.vue'
import ResultsView from '@/views/ResultsView.vue'
import PlayerStatsView from '@/views/PlayerStatsView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'standings', component: StandingsView },
    { path: '/results', name: 'results', component: ResultsView },
    { path: '/players/:id', name: 'player', component: PlayerStatsView },
    { path: '/entry', name: 'entry', component: EntryView },
  ],
})

export default router
