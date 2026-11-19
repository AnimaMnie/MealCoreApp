// Grupy użytkownika + aktywna grupa (używana przez listę zakupów i pasek górny).
import { reactive } from 'vue'
import { api } from './api'

const KEY = 'mc_active_group'

export const groups = reactive({
  list: [],
  loaded: false,
  activeId: Number(localStorage.getItem(KEY)) || null,

  get active() {
    return this.list.find((g) => g.id === this.activeId) ?? null
  },

  async load() {
    const res = await api.get('/groups')
    this.list = res.data
    this.loaded = true
    if (!this.active) this.setActive(this.list[0]?.id ?? null)
  },

  setActive(id) {
    this.activeId = id
    try {
      if (id) localStorage.setItem(KEY, String(id))
      else localStorage.removeItem(KEY)
    } catch { /* ignoruj */ }
  },

  reset() {
    this.list = []
    this.loaded = false
    this.setActive(null)
  },
})
