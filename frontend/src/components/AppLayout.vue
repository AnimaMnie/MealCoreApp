<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import { auth } from '../auth'
import { groups } from '../groups'
import { toastError } from '../toast'

const route = useRoute()
const router = useRouter()
const sidebarOpen = ref(false)
const groupMenuOpen = ref(false)

const initial = computed(() => (auth.user?.name || '?').charAt(0).toUpperCase())
const roleLabel = computed(() => (auth.isAdmin ? 'administrator' : 'użytkownik'))

const links = computed(() => [
  { to: '/dashboard', icon: 'fa-table-columns', label: 'Pulpit' },
  { to: '/meal-log', icon: 'fa-utensils', label: 'Dziennik posiłków' },
  { to: '/shopping-list', icon: 'fa-cart-shopping', label: 'Lista zakupów' },
  { to: '/groups', icon: 'fa-users', label: 'Grupy' },
  { to: '/products', icon: 'fa-carrot', label: 'Produkty' },
  ...(auth.isAdmin ? [{ to: '/users', icon: 'fa-users-gear', label: 'Użytkownicy' }] : []),
])

onMounted(async () => {
  try {
    if (!groups.loaded) await groups.load()
  } catch (e) {
    toastError(e)
  }
})

// Zamknij menu mobilne po przejściu na inną stronę.
watch(() => route.path, () => { sidebarOpen.value = false })

function switchGroup(id) {
  groups.setActive(id)
  groupMenuOpen.value = false
}

async function logout() {
  try {
    await api.post('/auth/logout')
  } catch { /* token i tak usuwamy lokalnie */ }
  auth.clear()
  groups.reset()
  router.push('/login')
}
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar" :class="{ open: sidebarOpen }">
      <RouterLink to="/dashboard" class="sidebar-brand">
        <span class="logo">MealCore</span>
        <span class="tagline">Paliwo dla witalności</span>
      </RouterLink>

      <nav class="sidebar-nav">
        <RouterLink v-for="link in links" :key="link.to" :to="link.to" active-class="active">
          <i class="fa-solid" :class="link.icon"></i> {{ link.label }}
        </RouterLink>
      </nav>

      <RouterLink to="/meal-log" class="sidebar-cta"><i class="fa-solid fa-plus"></i> Zaplanuj posiłek</RouterLink>

      <div class="sidebar-user">
        <div class="su-link">
          <div class="avatar">{{ initial }}</div>
          <div class="meta">
            <div class="name">{{ auth.user?.name }}</div>
            <div class="role">{{ roleLabel }}</div>
          </div>
        </div>
        <a href="#" title="Wyloguj" class="logout-link" @click.prevent="logout">
          <i class="fa-solid fa-right-from-bracket"></i>
        </a>
      </div>
    </aside>
    <div class="scrim" :class="{ open: sidebarOpen }" @click="sidebarOpen = false"></div>

    <nav class="bottom-nav">
      <RouterLink to="/dashboard" active-class="active"><i class="fa-solid fa-table-columns"></i><span>Pulpit</span></RouterLink>
      <RouterLink to="/meal-log" active-class="active"><i class="fa-solid fa-utensils"></i><span>Dziennik</span></RouterLink>
      <RouterLink to="/shopping-list" active-class="active"><i class="fa-solid fa-cart-shopping"></i><span>Zakupy</span></RouterLink>
      <RouterLink to="/groups" active-class="active"><i class="fa-solid fa-users"></i><span>Grupy</span></RouterLink>
    </nav>

    <div class="content">
      <header class="topbar">
        <div class="topbar-left">
          <button class="hamburger" aria-label="Menu" @click="sidebarOpen = !sidebarOpen">
            <i class="fa-solid fa-bars"></i>
          </button>
          <RouterLink v-if="groups.active" to="/groups" class="topbar-group" title="Zarządzaj grupami">
            <i class="fa-solid fa-users"></i> {{ groups.active.name }}
          </RouterLink>
        </div>
        <div class="topbar-right">
          <div class="group-switcher" :class="{ open: groupMenuOpen }">
            <button class="switch-group" type="button" @click="groupMenuOpen = !groupMenuOpen">
              Zmień grupę <i class="fa-solid fa-chevron-down caret"></i>
            </button>
            <div v-if="groupMenuOpen" class="group-menu">
              <div class="gm-label">Przełącz na</div>
              <div v-if="groups.list.length === 0" class="gm-empty">Nie należysz jeszcze do żadnej grupy.</div>
              <button
                v-for="g in groups.list"
                :key="g.id"
                type="button"
                class="gm-item"
                :class="{ active: g.id === groups.activeId }"
                @click="switchGroup(g.id)"
              >
                <i class="fa-solid fa-users"></i>
                <span class="gm-name">{{ g.name }}</span>
                <i v-if="g.id === groups.activeId" class="fa-solid fa-check gm-check"></i>
              </button>
              <RouterLink to="/groups" class="gm-manage" @click="groupMenuOpen = false">
                <i class="fa-solid fa-gear"></i> Zarządzaj grupami
              </RouterLink>
            </div>
          </div>
        </div>
      </header>

      <slot />
    </div>
  </div>
</template>
