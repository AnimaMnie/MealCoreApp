<script setup>
import { reactive, ref, watch } from 'vue'
import { api, fieldError } from '../api'
import { auth } from '../auth'
import { CATEGORIES, categoryIcon, fmt } from '../labels'
import { toast, toastError } from '../toast'
import Pager from '../components/Pager.vue'

const products = ref([])
const meta = ref(null)
const filters = reactive({ q: '', category: '', page: 1 })
const loading = ref(false)

const emptyForm = () => ({ name: '', category: 'Inne', kcal_100g: '', protein_100g: '', carbs_100g: '', fat_100g: '' })
const modal = reactive({ open: false, id: null, form: emptyForm(), error: null })

async function load() {
  loading.value = true
  try {
    const params = new URLSearchParams({ page: filters.page, per_page: 10 })
    if (filters.q.trim()) params.set('q', filters.q.trim())
    if (filters.category) params.set('category', filters.category)
    const res = await api.get(`/products?${params}`)
    products.value = res.data
    meta.value = res.meta
  } catch (e) {
    toastError(e)
  } finally {
    loading.value = false
  }
}

let timer = null
watch(() => [filters.q, filters.category], () => {
  clearTimeout(timer)
  timer = setTimeout(() => { filters.page = 1; load() }, 250)
})
watch(() => filters.page, load, { immediate: true })

function openCreate() {
  Object.assign(modal, { open: true, id: null, form: emptyForm(), error: null })
}

function openEdit(p) {
  Object.assign(modal, {
    open: true,
    id: p.id,
    error: null,
    form: { name: p.name, category: p.category, kcal_100g: p.per_100g.kcal, protein_100g: p.per_100g.protein, carbs_100g: p.per_100g.carbs, fat_100g: p.per_100g.fat },
  })
}

async function save() {
  modal.error = null
  const payload = Object.fromEntries(Object.entries(modal.form).filter(([, v]) => v !== '' && v !== null))
  try {
    if (modal.id) {
      await api.patch(`/products/${modal.id}`, payload)
      toast('Produkt zaktualizowany')
    } else {
      await api.post('/products', payload)
      toast('Produkt dodany')
    }
    modal.open = false
    await load()
  } catch (e) {
    modal.error = e
    if (!Object.keys(e.errors).length) toastError(e)
  }
}

async function remove(p) {
  if (!confirm(`Usunąć produkt „${p.name}”?`)) return
  try {
    await api.delete(`/products/${p.id}`)
    toast('Produkt usunięty')
    await load()
  } catch (e) {
    toastError(e) // 409: produkt użyty w posiłku
  }
}
</script>

<template>
  <div class="page">
    <div class="page-head" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
      <div>
        <h1 class="page-title">Baza <span class="accent">produktów</span></h1>
        <p class="page-sub">Wartości odżywcze na 100 g. {{ auth.isAdmin ? 'Jako admin możesz dodawać, edytować i usuwać produkty.' : 'Produkty dodaje administrator.' }}</p>
      </div>
      <button v-if="auth.isAdmin" class="btn-orange" @click="openCreate"><i class="fa-solid fa-plus"></i> Nowy produkt</button>
    </div>

    <div class="filter-bar">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input v-model="filters.q" type="text" placeholder="Szukaj produktu…" />
      </div>
      <select v-model="filters.category">
        <option value="">Wszystkie kategorie</option>
        <option v-for="c in CATEGORIES" :key="c">{{ c }}</option>
      </select>
    </div>

    <div class="table-wrap">
      <table class="user-table">
        <thead>
          <tr>
            <th>Produkt</th>
            <th>Kategoria</th>
            <th class="num">kcal</th>
            <th class="num">Białko</th>
            <th class="num">Węgle</th>
            <th class="num">Tłuszcz</th>
            <th v-if="auth.isAdmin">Akcje</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in products" :key="p.id">
            <td style="font-weight:700"><i class="fa-solid" :class="categoryIcon(p.category)" style="color:var(--orange);width:18px"></i> {{ p.name }}</td>
            <td><span class="pill">{{ p.category }}</span></td>
            <td class="num">{{ fmt(p.per_100g.kcal) }}</td>
            <td class="num">{{ p.per_100g.protein }} g</td>
            <td class="num">{{ p.per_100g.carbs }} g</td>
            <td class="num">{{ p.per_100g.fat }} g</td>
            <td v-if="auth.isAdmin">
              <div class="row-actions">
                <button class="icon-btn" title="Edytuj" @click="openEdit(p)"><i class="fa-solid fa-pen"></i></button>
                <button class="icon-btn danger" title="Usuń" @click="remove(p)"><i class="fa-solid fa-trash-can"></i></button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && products.length === 0">
            <td :colspan="auth.isAdmin ? 7 : 6" class="sel-empty">Brak produktów spełniających kryteria.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pager :meta="meta" @page="filters.page = $event" />

    <div class="modal-overlay" :class="{ open: modal.open }" @click.self="modal.open = false">
      <div class="modal" style="max-width:520px">
        <div class="modal-head">
          <div class="mh-icon"><i class="fa-solid" :class="modal.id ? 'fa-pen' : 'fa-plus'"></i></div>
          <div>
            <h3>{{ modal.id ? 'Edytuj produkt' : 'Nowy produkt' }}</h3>
            <p>Wartości na 100 g produktu</p>
          </div>
          <button class="close" @click="modal.open = false">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-field full">
              <label>Nazwa</label>
              <input v-model="modal.form.name" maxlength="120" />
              <div class="field-error">{{ fieldError(modal.error, 'name') }}</div>
            </div>
            <div class="form-field full">
              <label>Kategoria</label>
              <select v-model="modal.form.category">
                <option v-for="c in CATEGORIES" :key="c">{{ c }}</option>
              </select>
            </div>
            <div v-for="[key, label] in [['kcal_100g', 'Kalorie (kcal)'], ['protein_100g', 'Białko (g)'], ['carbs_100g', 'Węglowodany (g)'], ['fat_100g', 'Tłuszcz (g)']]" :key="key" class="form-field">
              <label>{{ label }}</label>
              <input v-model="modal.form[key]" type="number" min="0" step="0.1" />
              <div class="field-error">{{ fieldError(modal.error, key) }}</div>
            </div>
          </div>
        </div>
        <div class="modal-foot">
          <div class="modal-actions">
            <button class="confirm" @click="save"><i class="fa-solid fa-floppy-disk"></i> Zapisz</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
