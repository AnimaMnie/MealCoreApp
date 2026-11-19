<script setup>
import { reactive, ref, watch } from 'vue'
import { api } from '../api'
import { auth } from '../auth'
import { fmt } from '../labels'
import { toast, toastError } from '../toast'
import Pager from '../components/Pager.vue'

const users = ref([])
const meta = ref(null)
const filters = reactive({ q: '', page: 1 })

async function load() {
  try {
    const params = new URLSearchParams({ page: filters.page, per_page: 10 })
    if (filters.q.trim()) params.set('q', filters.q.trim())
    const res = await api.get(`/admin/users?${params}`)
    users.value = res.data
    meta.value = res.meta
  } catch (e) {
    toastError(e)
  }
}

let timer = null
watch(() => filters.q, () => {
  clearTimeout(timer)
  timer = setTimeout(() => { filters.page = 1; load() }, 250)
})
watch(() => filters.page, load, { immediate: true })

async function changeRole(user, role) {
  try {
    await api.patch(`/admin/users/${user.id}/role`, { role })
    toast(`Zmieniono rolę: ${user.name} → ${role === 'admin' ? 'Admin' : 'Użytkownik'}`)
  } catch (e) {
    toastError(e) // 409: nie można odebrać uprawnień samemu sobie
  }
  await load()
}

async function remove(user) {
  if (!confirm(`Usunąć konto ${user.name}? Tej operacji nie można cofnąć.`)) return
  try {
    await api.delete(`/admin/users/${user.id}`)
    toast('Konto usunięte')
    await load()
  } catch (e) {
    toastError(e)
  }
}
</script>

<template>
  <div class="page">
    <div class="page-head">
      <h1 class="page-title">Zarządzanie <span class="accent">użytkownikami</span></h1>
      <p class="page-sub">Konta, role i dostęp w MealCore.</p>
    </div>

    <div class="filter-bar">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input v-model="filters.q" type="text" placeholder="Szukaj po nazwie lub e-mailu…" />
      </div>
    </div>

    <div class="table-wrap">
      <table class="user-table">
        <thead>
          <tr>
            <th>Użytkownik</th>
            <th>Rola</th>
            <th>Cel kalorii</th>
            <th>Akcje</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>
              <div style="display:flex;align-items:center;gap:12px;">
                <div class="sidebar-user" style="padding:0;"><div class="avatar">{{ u.name.charAt(0).toUpperCase() }}</div></div>
                <div>
                  <div style="font-weight:700;">{{ u.name }} <span v-if="u.id === auth.user?.id" class="pill">Ty</span></div>
                  <div style="font-size:12px;color:var(--ink-soft);">{{ u.email }}</div>
                </div>
              </div>
            </td>
            <td><span class="badge" :class="u.role === 'admin' ? 'admin' : 'user'">{{ u.role === 'admin' ? 'Admin' : 'Użytkownik' }}</span></td>
            <td>{{ fmt(u.daily_calorie_goal) }} kcal</td>
            <td>
              <div class="row-actions">
                <select class="role-select" :value="u.role" @change="changeRole(u, $event.target.value)">
                  <option value="user">Użytkownik</option>
                  <option value="admin">Admin</option>
                </select>
                <button class="icon-btn danger" title="Usuń konto" @click="remove(u)">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pager :meta="meta" @page="filters.page = $event" />
  </div>
</template>
