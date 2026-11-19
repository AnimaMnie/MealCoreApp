<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { api, fieldError } from '../api'
import { auth } from '../auth'
import { CATEGORIES, MEAL_TYPES, categoryIcon, fmt } from '../labels'
import { toast, toastError } from '../toast'

/*
 * Modal "Dodaj / edytuj posiłek" (jak "Add Meal" w starym projekcie).
 * - meal = null  -> POST /api/meals (nowy posiłek)
 * - meal = {...} -> PATCH /api/meals/{id} (edycja: zmiana gramatury, usuwanie, dodawanie pozycji)
 */
const props = defineProps({
  open: Boolean,
  date: { type: String, required: true },
  mealType: { type: String, default: 'breakfast' },
  meal: { type: Object, default: null },
})
const emit = defineEmits(['close', 'saved'])

const type = ref(props.mealType)
const selection = ref([]) // { product_id, name, category, per100: {kcal, protein, carbs, fat}, grams }
const query = ref('')
const results = ref([])
const saving = ref(false)

// Formularz nowego produktu - tylko admin (API zwróci 403 dla zwykłego użytkownika).
const showCreate = ref(false)
const newProduct = reactive({ name: '', category: 'Inne', kcal_100g: '', protein_100g: '', carbs_100g: '', fat_100g: '' })
const createError = ref(null)

watch(() => props.open, (isOpen) => {
  if (!isOpen) return
  type.value = props.meal?.meal_type ?? props.mealType
  query.value = ''
  results.value = []
  showCreate.value = false
  selection.value = (props.meal?.items ?? []).map((item) => ({
    product_id: item.product_id,
    name: item.product_name,
    category: '',
    // Wartości na 100 g odtwarzamy z wartości porcji zwróconych przez API.
    per100: Object.fromEntries(Object.entries(item.nutrition).map(([k, v]) => [k, (v / item.grams) * 100])),
    grams: item.grams,
  }))
})

// Wyszukiwarka produktów z opóźnieniem (debounce), żeby nie wysyłać requestu po każdej literze.
let timer = null
watch(query, (q) => {
  clearTimeout(timer)
  if (q.trim().length < 2) { results.value = []; searching.value = false; return }
  searching.value = true
  timer = setTimeout(async () => {
    try {
      const res = await api.get(`/products?q=${encodeURIComponent(q.trim())}&per_page=8`)
      if (q === query.value) results.value = res.data
    } catch (e) {
      toastError(e)
    } finally {
      if (q === query.value) searching.value = false
    }
  }, 250)
})
const searching = ref(false)

function addProduct(product) {
  selection.value.push({
    product_id: product.id,
    name: product.name,
    category: product.category,
    per100: product.per_100g,
    grams: 100,
  })
  query.value = ''
  results.value = []
}

const portion = (s, key) => ((s.per100[key] || 0) * (Number(s.grams) || 0)) / 100
const totals = computed(() => {
  const t = { kcal: 0, protein: 0, carbs: 0, fat: 0 }
  for (const s of selection.value) for (const k of Object.keys(t)) t[k] += portion(s, k)
  return t
})

async function createProduct() {
  createError.value = null
  try {
    const payload = Object.fromEntries(Object.entries(newProduct).filter(([, v]) => v !== ''))
    const res = await api.post('/products', payload)
    addProduct(res.data)
    toast('Produkt dodany do bazy')
    Object.assign(newProduct, { name: '', category: 'Inne', kcal_100g: '', protein_100g: '', carbs_100g: '', fat_100g: '' })
    showCreate.value = false
  } catch (e) {
    createError.value = e
    if (!Object.keys(e.errors).length) toastError(e)
  }
}

async function confirm() {
  if (selection.value.length === 0) {
    toast('Dodaj przynajmniej jeden produkt', true)
    return
  }
  saving.value = true
  const items = selection.value.map((s) => ({ product_id: s.product_id, grams: Number(s.grams) }))
  try {
    if (props.meal) {
      await api.patch(`/meals/${props.meal.id}`, { meal_type: type.value, items })
      toast('Posiłek zaktualizowany')
    } else {
      const label = MEAL_TYPES.find((t) => t.value === type.value)?.label
      await api.post('/meals', { meal_date: props.date, meal_type: type.value, name: label, items })
      toast('Posiłek zapisany')
    }
    emit('saved')
  } catch (e) {
    // Np. 422 "Nie można zapisać posiłku z przyszłą datą." albo błąd walidacji gramatury.
    const firstFieldError = Object.values(e.errors || {})[0]?.[0]
    toast(firstFieldError || e.message, true)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="modal-overlay" :class="{ open }" @click.self="emit('close')">
    <div class="modal">
      <div class="modal-head">
        <div class="mh-icon"><i class="fa-solid" :class="meal ? 'fa-pen' : 'fa-plus'"></i></div>
        <div>
          <h3>{{ meal ? 'Edytuj posiłek' : 'Dodaj posiłek' }}</h3>
          <p>Zapisz, co zjadłeś</p>
        </div>
        <button class="close" @click="emit('close')">&times;</button>
      </div>

      <div class="modal-body">
        <div class="field-row">
          <select v-model="type">
            <option v-for="t in MEAL_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>

        <div class="section-label">Znajdź produkt</div>
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input v-model="query" type="text" placeholder="Szukaj w bazie produktów…" autocomplete="off" />
        </div>
        <div class="search-results">
          <div v-for="p in results" :key="p.id" class="res" @click="addProduct(p)">
            <i class="fa-solid" :class="categoryIcon(p.category)" style="color:var(--orange)"></i>
            <span class="rname">{{ p.name }}</span>
            <span class="rk">{{ fmt(p.per_100g.kcal) }} kcal / 100 g</span>
          </div>
          <div v-if="searching" class="sel-empty"><i class="fa-solid fa-spinner fa-spin"></i> Szukam…</div>
          <div v-else-if="query.trim().length >= 2 && results.length === 0" class="sel-empty">Brak wyników.</div>
        </div>

        <template v-if="auth.isAdmin">
          <button type="button" class="create-toggle" @click="showCreate = !showCreate">
            <i class="fa-solid fa-circle-plus"></i> Nie ma produktu? Dodaj go do bazy
          </button>
          <div v-if="showCreate" class="create-form">
            <div class="section-label">Nowy produkt (wartości na 100 g)</div>
            <div class="cf-row">
              <input v-model="newProduct.name" type="text" maxlength="120" placeholder="Nazwa (np. Kanapka z pastą)" />
            </div>
            <div class="field-error">{{ fieldError(createError, 'name') }}</div>
            <div class="cf-row">
              <select v-model="newProduct.category">
                <option v-for="c in CATEGORIES" :key="c">{{ c }}</option>
              </select>
            </div>
            <div class="cf-grid">
              <label>kcal<input v-model="newProduct.kcal_100g" type="number" min="0" max="900" /></label>
              <label>Białko<input v-model="newProduct.protein_100g" type="number" min="0" max="100" step="0.1" /></label>
              <label>Węgle<input v-model="newProduct.carbs_100g" type="number" min="0" max="100" step="0.1" /></label>
              <label>Tłuszcz<input v-model="newProduct.fat_100g" type="number" min="0" max="100" step="0.1" /></label>
            </div>
            <div class="field-error">{{ fieldError(createError, 'kcal_100g') }}</div>
            <button type="button" class="cf-save" @click="createProduct">
              <i class="fa-solid fa-floppy-disk"></i> Zapisz i dodaj do posiłku
            </button>
          </div>
        </template>

        <div class="sel-head">
          <span class="t">Wybrane produkty</span>
          <span class="pill">{{ selection.length }} poz.</span>
        </div>
        <div v-if="selection.length === 0" class="sel-empty">Nie wybrano jeszcze produktów.</div>
        <div v-for="(s, i) in selection" :key="i" class="sel-item">
          <div class="si-icon"><i class="fa-solid" :class="categoryIcon(s.category)"></i></div>
          <div class="si-info">
            <div class="si-name">{{ s.name }}</div>
            <div class="si-kpg">{{ fmt(s.per100.kcal) }} kcal / 100 g</div>
          </div>
          <div class="si-weight">
            <label>Waga</label>
            <div class="grams-wrap">
              <input v-model="s.grams" type="number" class="grams" min="1" max="5000" step="1" />
              <span class="unit">g</span>
            </div>
          </div>
          <div class="si-kcal"><div class="n">{{ fmt(portion(s, 'kcal')) }}</div><div class="u">KCAL</div></div>
          <button class="si-del" title="Usuń" @click="selection.splice(i, 1)"><i class="fa-solid fa-trash-can"></i></button>
        </div>
      </div>

      <div class="modal-foot">
        <div class="totals-row">
          <div class="total-ring"><span class="n">{{ fmt(totals.kcal) }}</span><span class="u">KCAL</span></div>
          <div class="macro-mini"><div class="k">Białko</div><div class="v">{{ fmt(totals.protein) }}g</div></div>
          <div class="macro-mini"><div class="k">Węgle</div><div class="v">{{ fmt(totals.carbs) }}g</div></div>
          <div class="macro-mini"><div class="k">Tłuszcz</div><div class="v">{{ fmt(totals.fat) }}g</div></div>
        </div>
        <div class="modal-actions">
          <button class="confirm" :disabled="saving" @click="confirm">
            <i class="fa-solid fa-circle-check"></i> {{ meal ? 'Zapisz zmiany' : 'Zatwierdź posiłek' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
