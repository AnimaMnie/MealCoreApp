// Krótkie powiadomienia na dole ekranu (klasa .toast z app.css).
import { reactive } from 'vue'

export const toastState = reactive({ message: '', error: false, visible: false })
let timer = null

export function toast(message, error = false) {
  toastState.message = message
  toastState.error = error
  toastState.visible = true
  clearTimeout(timer)
  timer = setTimeout(() => { toastState.visible = false }, 3000)
}

// Wyświetla błąd z API (np. 409 reguły biznesowej, 403, 422).
export function toastError(e) {
  toast(e?.message || 'Coś poszło nie tak.', true)
}
