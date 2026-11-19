<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { api } from '../api'
import { groups } from '../groups'
import { CATEGORIES } from '../labels'
import { toast, toastError } from '../toast'

const items = ref([])
const loading = ref(false)
const form = reactive({ product_name: '', quantity: '', category: 'Warzywa i owoce' })
const editing = ref(null) // { id, product_name, quantity }

async function load() {
  if (!groups.activeId) { items.value = []; return }
  loading.value = true
  try {
    const res = await api.get(`/groups/${groups.activeId}/shopping-items?per_page=50`)
    items.value = res.data
  } catch (e) {
    toastError(e)
  } finally {
    loading.value = false
  }
}
watch(() => groups.activeId, load, { immediate: true })

const pending = computed(() => items.value.filter((i) => !i.is_checked))
const bought = computed(() => items.value.filter((i) => i.is_checked))
const progress = computed(() => (items.value.length ? Math.round((bought.value.length / items.value.length) * 100) : 0))
const ring = 2 * Math.PI * 52

async function add() {
  if (!form.product_name.trim()) { toast('Wpisz nazwę produktu', true); return }
  try {
    const payload = { product_name: form.product_name.trim(), category: form.category }
    if (form.quantity.trim()) payload.quantity = form.quantity.trim()
    await api.post(`/groups/${groups.activeId}/shopping-items`, payload)
    form.product_name = ''
    form.quantity = ''
    toast('Dodano do listy')
    await load()
  } catch (e) {
    toastError(e) // np. 409 "Ten produkt jest już na liście zakupów."
  }
}

async function toggle(item) {
  try {
    await api.patch(`/shopping-items/${item.id}`, { is_checked: !item.is_checked })
    await load()
  } catch (e) {
    toastError(e)
  }
}

async function saveEdit() {
  try {
    await api.patch(`/shopping-items/${editing.value.id}`, {
      product_name: editing.value.product_name,
      quantity: editing.value.quantity || '1',
    })
    editing.value = null
    toast('Zapisano zmiany')
    await load()
  } catch (e) {
    toastError(e)
  }
}

async function remove(item) {
  try {
    await api.delete(`/shopping-items/${item.id}`)
    toast('Usunięto z listy')
    await load()
  } catch (e) {
    toastError(e)
  }
}

const timeAgo = (iso) => (iso ? new Date(iso).toLocaleString('pl-PL', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '')
</script>

<template>
  <div class="page">
    <div class="page-head">
      <h1 class="page-title">Wspólna <span class="accent">spiżarnia</span></h1>
      <p v-if="groups.active" class="page-sub">
        Lista grupy <span class="accent">{{ groups.active.name }}</span> — do kupienia: {{ pending.length }}
      </p>
    </div>

    <div v-if="groups.loaded && !groups.active" class="empty-meal">
      <i class="fa-solid fa-users"></i>
      Nie należysz do żadnej grupy. <RouterLink to="/groups" class="accent">&nbsp;Utwórz grupę</RouterLink>, aby mieć wspólną listę zakupów.
    </div>

    <div v-else class="shop-grid">
      <div>
        <form class="add-bar" @submit.prevent="add">
          <input v-model="form.product_name" type="text" class="txt" placeholder="Dodaj awokado, chleb na zakwasie, mleko…" maxlength="120" />
          <div class="qty-wrap" title="Ilość">
            <i class="fa-solid fa-hashtag"></i>
            <input v-model="form.quantity" type="text" placeholder="1 opak., 500 g" maxlength="40" />
          </div>
          <select v-model="form.category">
            <option v-for="c in CATEGORIES" :key="c">{{ c }}</option>
          </select>
          <button type="submit" class="btn-orange"><i class="fa-solid fa-paper-plane"></i> Dodaj</button>
        </form>

        <div class="list-head"><h3>Do kupienia</h3></div>
        <div v-if="loading" class="loading"><i class="fa-solid fa-spinner fa-spin"></i></div>
        <div v-else-if="pending.length === 0" class="empty-meal">
          <i class="fa-solid fa-cart-shopping"></i> Lista jest pusta. Dodaj coś powyżej.
        </div>

        <div v-for="item in pending" :key="item.id" class="shop-item">
          <button class="shop-check" title="Oznacz jako kupione" @click="toggle(item)"></button>
          <div v-if="editing?.id === item.id" class="info" style="display:flex;gap:8px;">
            <input v-model="editing.product_name" class="pf-input" maxlength="120" />
            <input v-model="editing.quantity" class="pf-input" style="max-width:120px" maxlength="40" />
            <button class="btn-orange" @click="saveEdit"><i class="fa-solid fa-check"></i></button>
            <button class="btn-ghost" @click="editing = null"><i class="fa-solid fa-xmark"></i></button>
          </div>
          <div v-else class="info">
            <div class="sname">{{ item.product_name }}</div>
            <div class="smeta">Dodał(a) <b>{{ item.added_by || 'ktoś' }}</b> · {{ item.quantity }}</div>
          </div>
          <span class="pill">{{ item.category }}</span>
          <button class="icon-btn" title="Edytuj" @click="editing = { id: item.id, product_name: item.product_name, quantity: item.quantity }">
            <i class="fa-solid fa-pen"></i>
          </button>
          <button class="del" title="Usuń" @click="remove(item)"><i class="fa-solid fa-trash-can"></i></button>
        </div>

        <template v-if="bought.length">
          <div class="list-head" style="margin-top:24px;"><h3>Ostatnio kupione</h3></div>
          <div v-for="item in bought" :key="item.id" class="shop-item done">
            <button class="shop-check" title="Cofnij" @click="toggle(item)"><i class="fa-solid fa-check"></i></button>
            <div class="info">
              <div class="sname">{{ item.product_name }}</div>
              <div class="smeta">Kupiono {{ timeAgo(item.bought_at) }} · {{ item.quantity }}</div>
            </div>
            <span class="pill">{{ item.category }}</span>
            <button class="del" title="Usuń" @click="remove(item)"><i class="fa-solid fa-trash-can"></i></button>
          </div>
        </template>
      </div>

      <div class="side-stack">
        <div class="card" style="text-align:center;">
          <h4>Postęp zakupów</h4>
          <div class="progress-ring">
            <svg width="130" height="130" viewBox="0 0 130 130">
              <circle cx="65" cy="65" r="52" fill="none" stroke="var(--orange-soft)" stroke-width="12" />
              <circle cx="65" cy="65" r="52" fill="none" stroke="var(--orange)" stroke-width="12" stroke-linecap="round"
                      :stroke-dasharray="ring" :stroke-dashoffset="ring * (1 - progress / 100)" />
            </svg>
            <div class="center">
              <span class="pct">{{ progress }}%</span>
              <span class="lbl">kupione</span>
            </div>
          </div>
          <p class="sub" style="color:var(--ink-soft);font-size:13px;">Odhaczaj produkty podczas zakupów.</p>
        </div>
      </div>
    </div>
  </div>
</template>
