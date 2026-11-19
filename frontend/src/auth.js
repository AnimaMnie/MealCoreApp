// Stan logowania (authentication po stronie frontendu): token + dane użytkownika.
// Token trzymamy w localStorage, żeby odświeżenie strony nie wylogowywało.
import { reactive } from 'vue'

const read = (key) => {
  try { return localStorage.getItem(key) } catch { return null }
}
const write = (key, value) => {
  try {
    if (value === null) localStorage.removeItem(key)
    else localStorage.setItem(key, value)
  } catch { /* tryb prywatny itp. */ }
}

export const auth = reactive({
  token: read('mc_token'),
  user: JSON.parse(read('mc_user') || 'null'),

  get isLoggedIn() { return !!this.token },
  get isAdmin() { return this.user?.role === 'admin' },

  set(token, user) {
    this.token = token
    this.user = user
    write('mc_token', token)
    write('mc_user', JSON.stringify(user))
  },

  clear() {
    this.token = null
    this.user = null
    write('mc_token', null)
    write('mc_user', null)
  },
})
