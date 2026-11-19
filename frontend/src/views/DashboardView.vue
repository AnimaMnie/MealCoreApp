<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { api } from '../api'
import { auth } from '../auth'
import { fmt, formatDate } from '../labels'
import { toastError } from '../toast'
import ProgressRing from '../components/ProgressRing.vue'

const range = ref('daily') // daily | weekly | monthly
const summary = ref(null) // GET /meals/summary (dziś)
const history = ref(null) // GET /meals/history (z kolejki)
const month = ref(null)
const loading = ref(true)

const RANGE_DAYS = { daily: 1, weekly: 7, monthly: 30 }
const RANGE_TITLE = { daily: 'Dzienne', weekly: 'Tygodniowe', monthly: 'Miesięczne' }

async function load() {
  loading.value = true
  try {
    const [s, w, m] = await Promise.all([
      api.get('/meals/summary'),
      api.get('/meals/history?days=7'),
      api.get('/meals/history?days=30'),
    ])
    summary.value = s.data
    history.value = w.data
    month.value = m.data
  } catch (e) {
    toastError(e)
  } finally {
    loading.value = false
  }
}
onMounted(load)
watch(range, () => {}) // zakres zmienia tylko obliczenia poniżej

const goal = computed(() => auth.user?.daily_calorie_goal || summary.value?.calorie_goal || 2000)

// Statystyki dla wybranego zakresu: dziś -> summary, tydzień/miesiąc -> historia z daily_summaries.
const stats = computed(() => {
  if (!summary.value) return null
  if (range.value === 'daily') {
    return { ...summary.value.totals, meals: summary.value.meals_count, days: 1 }
  }
  const days = range.value === 'weekly' ? history.value.days : month.value.days
  const sum = (k) => days.reduce((acc, d) => acc + Number(d[k]), 0)
  return { kcal: sum('kcal'), protein: sum('protein'), carbs: sum('carbs'), fat: sum('fat'), meals: sum('meals_count'), days: days.length }
})

const periodGoal = computed(() => goal.value * (stats.value?.days || 1))
const calPct = computed(() => (stats.value ? Math.round((stats.value.kcal / periodGoal.value) * 100) : 0))
const remaining = computed(() => Math.max(0, periodGoal.value - (stats.value?.kcal || 0)))
const onTrack = computed(() => calPct.value <= 105)

// Cele makro z celu kalorycznego: 25% białko, 50% węglowodany, 25% tłuszcz.
const macroGoals = computed(() => ({
  protein: Math.round((periodGoal.value * 0.25) / 4),
  carbs: Math.round((periodGoal.value * 0.5) / 4),
  fat: Math.round((periodGoal.value * 0.25) / 9),
}))
const pct = (v, g) => (g ? Math.min(100, Math.round((v / g) * 100)) : 0)

// Seria dni z zapisanym posiłkiem (licząc od dziś lub od wczoraj).
const streak = computed(() => {
  if (!month.value) return 0
  const days = [...month.value.days].reverse()
  let i = days[0]?.meals_count > 0 ? 0 : 1
  let count = 0
  while (i < days.length && days[i].meals_count > 0) { count++; i++ }
  return count
})

const chartDays = computed(() => (range.value === 'monthly' ? month.value?.days : history.value?.days) || [])
const chartMax = computed(() => Math.max(goal.value, ...chartDays.value.map((d) => d.kcal)))

const greeting = computed(() => {
  const h = new Date().getHours()
  return h < 12 ? 'Dzień dobry' : h < 18 ? 'Miłego popołudnia' : 'Dobry wieczór'
})

const suggestion = computed(() => {
  const s = stats.value
  if (!s) return ''
  if (s.protein >= macroGoals.value.protein) return 'Świetnie z białkiem! Cel osiągnięty — trzymaj tempo.'
  if (remaining.value > periodGoal.value * 0.4) return 'Zostało Ci sporo kalorii. Zaplanuj zbilansowany posiłek z warzywami i białkiem.'
  if (s.fat > macroGoals.value.fat) return 'Jesteś trochę powyżej celu tłuszczu — wybierz chudsze opcje.'
  return 'Twój dziennik wygląda na zbilansowany. Dorzuć trochę warzyw dla błonnika.'
})
</script>

<template>
  <div class="page dash-page">
    <div class="page-head dash-head">
      <div>
        <h1 class="page-title">{{ greeting }}, {{ auth.user?.name }}.</h1>
        <p class="page-sub">
          <template v-if="range === 'daily'">Twój dziennik na {{ formatDate(new Date().toLocaleDateString('sv-SE')) }}.</template>
          <template v-else-if="range === 'weekly'">Twój dziennik z ostatnich 7 dni.</template>
          <template v-else>Twój dziennik z ostatnich 30 dni.</template>
        </p>
      </div>
      <div class="seg-toggle" role="tablist">
        <a v-for="(title, key) in { daily: 'Dzień', weekly: 'Tydzień', monthly: 'Miesiąc' }" :key="key"
           href="#" class="seg" :class="{ active: range === key }" @click.prevent="range = key">{{ title }}</a>
      </div>
    </div>

    <div v-if="loading" class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Ładowanie…</div>

    <template v-else-if="stats">
      <div class="dash-top">
        <div class="card stat-cal">
          <div class="sc-label">{{ range === 'daily' ? 'Kalorie dziś' : RANGE_TITLE[range] + ' kalorie' }}</div>
          <div class="sc-num">{{ fmt(stats.kcal) }}<small>kcal</small></div>
          <div class="sc-progress">
            <div class="sc-progress-head">
              <span>Postęp celu ({{ fmt(periodGoal) }} kcal)</span>
              <span class="accent">{{ calPct }}%</span>
            </div>
            <div class="prog-bar"><span :style="{ width: Math.min(100, calPct) + '%' }"></span></div>
          </div>
        </div>

        <div class="card stat-remain">
          <div class="sr-icon"><i class="fa-solid fa-bolt"></i></div>
          <div class="sr-label">Zostało</div>
          <div class="sr-num">{{ fmt(remaining) }}</div>
          <div class="sr-sub">kcal do wykorzystania</div>
        </div>

        <div class="card stat-meals">
          <div class="sm-top">
            <i class="fa-solid fa-utensils"></i>
            <span class="badge-track" :class="{ over: !onTrack }">{{ onTrack ? 'W normie' : 'Powyżej celu' }}</span>
          </div>
          <div class="sm-label">Liczba posiłków</div>
          <div class="sm-num">{{ stats.meals }}</div>
        </div>
      </div>

      <div class="dash-main">
        <div class="dash-left">
          <div class="card insights-card">
            <h3 class="card-title">{{ RANGE_TITLE[range] }} wartości odżywcze</h3>
            <div class="insights-grid">
              <div class="diversity">
                <div class="section-label">Bilans makroskładników</div>
                <div class="rings-grid">
                  <div class="mini-ring">
                    <div class="mr-svg"><ProgressRing :percent="pct(stats.protein, macroGoals.protein)" color="var(--macro-protein)" /><span>{{ Math.round(stats.protein) }}/{{ macroGoals.protein }}</span></div>
                    <div class="mr-name">Białko (g)</div>
                  </div>
                  <div class="mini-ring">
                    <div class="mr-svg"><ProgressRing :percent="pct(stats.carbs, macroGoals.carbs)" color="var(--macro-carbs)" /><span>{{ Math.round(stats.carbs) }}/{{ macroGoals.carbs }}</span></div>
                    <div class="mr-name">Węglowodany (g)</div>
                  </div>
                  <div class="mini-ring">
                    <div class="mr-svg"><ProgressRing :percent="pct(stats.fat, macroGoals.fat)" color="var(--macro-fat)" /><span>{{ Math.round(stats.fat) }}/{{ macroGoals.fat }}</span></div>
                    <div class="mr-name">Tłuszcz (g)</div>
                  </div>
                  <div class="mini-ring">
                    <div class="mr-svg"><ProgressRing :percent="calPct" color="var(--orange-dark)" /><span>{{ calPct }}%</span></div>
                    <div class="mr-name">Kalorie</div>
                  </div>
                </div>
              </div>

              <div class="insight-side">
                <div class="suggestion">
                  <div class="sg-head"><i class="fa-solid fa-lightbulb"></i> Podpowiedź</div>
                  <p>{{ suggestion }}</p>
                </div>

                <div class="hydration">
                  <div class="hy-head">
                    <span>{{ range === 'monthly' ? 'Ostatnie 30 dni' : 'Ostatnie 7 dni' }}</span>
                    <strong>śr. {{ fmt(range === 'monthly' ? month.average_kcal : history.average_kcal) }} kcal</strong>
                  </div>
                  <div class="week-chart" :class="{ dense: range === 'monthly' }">
                    <div v-for="d in chartDays" :key="d.date" class="bar-col" :title="`${d.date}: ${fmt(d.kcal)} kcal`">
                      <span class="bar-val">{{ d.kcal ? fmt(d.kcal) : '' }}</span>
                      <div class="bar" :class="{ over: d.goal_exceeded, empty: !d.meals_count }"
                           :style="{ height: Math.max(4, (d.kcal / chartMax) * 100) + 'px' }"></div>
                      <span v-if="range !== 'monthly'" class="bar-day">{{ formatDate(d.date, { weekday: 'short' }) }}</span>
                    </div>
                  </div>
                  <div class="chart-legend">
                    <span><span class="dot" style="background:var(--orange)"></span>w normie</span>
                    <span><span class="dot" style="background:var(--red)"></span>powyżej celu</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="dash-right">
          <div class="card upnext-card">
            <div class="un-head"><span class="pill">Następny posiłek</span></div>
            <h3 class="un-title">Zaplanuj kolejny posiłek</h3>
            <p class="un-sub">Zostało {{ fmt(Math.max(0, goal - summary.totals.kcal)) }} kcal na dziś</p>
            <RouterLink to="/meal-log" class="un-btn"><i class="fa-solid fa-plus"></i> Dodaj posiłek</RouterLink>
          </div>

          <div class="card streak-card">
            <div class="streak-icon"><i class="fa-solid fa-award"></i></div>
            <div class="streak-label">Seria dni</div>
            <div class="streak-num">{{ streak }} <span>{{ streak === 1 ? 'dzień' : 'dni' }}</span></div>
            <p class="streak-note">Regularne zapisywanie posiłków to klucz do sukcesu.</p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
