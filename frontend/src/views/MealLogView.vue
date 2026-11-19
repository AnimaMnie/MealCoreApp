<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import { MEAL_TYPES, fmt, formatDate, shiftDate, today } from '../labels'
import { toast, toastError } from '../toast'
import MealModal from '../components/MealModal.vue'

const route = useRoute()
const router = useRouter()

const date = computed(() => route.query.date || today())
const meals = ref([])
const loading = ref(true)

const modal = ref({ open: false, mealType: 'breakfast', meal: null })

async function load() {
  loading.value = true
  try {
    const res = await api.get(`/meals?date=${date.value}&per_page=50`)
    meals.value = res.data
  } catch (e) {
    toastError(e)
  } finally {
    loading.value = false
  }
}
watch(date, load, { immediate: true })

const goTo = (d) => router.push({ query: { date: d } })

const byType = (type) => meals.value.filter((m) => m.meal_type === type)
const typeKcal = (type) => byType(type).reduce((acc, m) => acc + m.totals.kcal, 0)
const totalKcal = computed(() => meals.value.reduce((acc, m) => acc + m.totals.kcal, 0))

function openModal(type) {
  // Jeśli posiłek tego typu już istnieje tego dnia -> edytujemy go, w przeciwnym razie tworzymy nowy.
  modal.value = { open: true, mealType: type, meal: byType(type)[0] ?? null }
}

function editMeal(meal) {
  modal.value = { open: true, mealType: meal.meal_type, meal }
}

async function deleteItem(meal, item) {
  try {
    const rest = meal.items.filter((i) => i.id !== item.id)
    if (rest.length === 0) {
      await api.delete(`/meals/${meal.id}`)
      toast('Posiłek usunięty')
    } else {
      await api.patch(`/meals/${meal.id}`, { items: rest.map((i) => ({ product_id: i.product_id, grams: i.grams })) })
      toast('Produkt usunięty z posiłku')
    }
    await load()
  } catch (e) {
    toastError(e)
  }
}

async function onSaved() {
  modal.value.open = false
  await load()
}
</script>

<template>
  <div class="page">
    <div class="page-head" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;">
      <div>
        <h1 class="page-title">Dziennik <span class="accent">posiłków</span></h1>
        <p class="page-sub" style="text-transform:capitalize">{{ formatDate(date) }}</p>
        <div class="date-pills">
          <a href="#" @click.prevent="goTo(shiftDate(date, -1))">Poprzedni dzień</a>
          <a href="#" :class="{ active: date === today() }" @click.prevent="goTo(today())">Dziś</a>
          <a href="#" @click.prevent="goTo(shiftDate(date, 1))">Następny dzień</a>
          <label class="date-jump" title="Wybierz datę">
            <i class="fa-solid fa-calendar-day"></i>
            <input type="date" :value="date" @change="goTo($event.target.value)" />
          </label>
        </div>
      </div>
      <div class="consumed">
        <div>
          <div class="label">Spożyte łącznie</div>
          <div class="num">{{ fmt(totalKcal) }} <small>kcal</small></div>
        </div>
        <div class="consumed-icon"><i class="fa-solid fa-bolt"></i></div>
      </div>
    </div>

    <div v-if="loading" class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Ładowanie…</div>

    <template v-else>
      <section v-for="t in MEAL_TYPES" :key="t.value" class="meal-section">
        <div class="meal-head">
          <div class="left">
            <div class="meal-icon"><i class="fa-solid" :class="t.icon"></i></div>
            <div>
              <div class="title">{{ t.label }}</div>
              <div class="meta">{{ byType(t.value).length ? fmt(typeKcal(t.value)) + ' kcal' : 'Nic nie zapisano' }}</div>
            </div>
          </div>
          <button class="btn-ghost" @click="openModal(t.value)"><i class="fa-solid fa-plus"></i> Dodaj produkt</button>
        </div>

        <template v-if="byType(t.value).length">
          <template v-for="meal in byType(t.value)" :key="meal.id">
            <div v-for="item in meal.items" :key="item.id" class="food-item">
              <div class="food-thumb"><i class="fa-solid fa-bowl-food"></i></div>
              <div class="info">
                <div class="fname">{{ item.product_name }}</div>
                <div class="fmeta">
                  {{ fmt(item.grams) }} g · {{ item.nutrition.protein }} g białka · {{ item.nutrition.carbs }} g węgli · {{ item.nutrition.fat }} g tłuszczu
                </div>
              </div>
              <div class="fkcal">
                <div class="n">{{ fmt(item.nutrition.kcal) }}</div>
                <div class="u">KCAL</div>
              </div>
              <button class="edit" title="Edytuj posiłek" @click="editMeal(meal)"><i class="fa-solid fa-pen"></i></button>
              <button class="del" title="Usuń" @click="deleteItem(meal, item)"><i class="fa-solid fa-trash-can"></i></button>
            </div>
          </template>
        </template>
        <div v-else class="empty-meal">
          <i class="fa-solid fa-utensils"></i>
          Brak zapisanych produktów
        </div>
      </section>
    </template>

    <MealModal
      :open="modal.open"
      :date="date"
      :meal-type="modal.mealType"
      :meal="modal.meal"
      @close="modal.open = false"
      @saved="onSaved"
    />
  </div>
</template>
