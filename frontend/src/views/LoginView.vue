<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, fieldError } from '../api'
import { auth } from '../auth'

const router = useRouter()
const form = reactive({ email: '', password: '' })
const error = ref(null)
const loading = ref(false)

async function submit() {
  error.value = null
  loading.value = true
  try {
    const res = await api.post('/auth/login', form)
    auth.set(res.token, res.user)
    router.push('/dashboard')
  } catch (e) {
    error.value = e
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-body">
    <nav class="login-nav">
      <div class="login-nav-inner">
        <RouterLink to="/login" class="login-logo">MealCore</RouterLink>
      </div>
    </nav>

    <main class="login-main">
      <div class="blob blob-top-left"></div>
      <div class="blob blob-bottom-right"></div>

      <div class="login-card-wrapper">
        <div class="login-card">
          <div class="brand-anchor">
            <div class="login-icon-pill"><i class="fa-solid fa-utensils" style="color:#874E00"></i></div>
            <h1 class="login-title">Witaj ponownie</h1>
            <p class="login-subtitle">Twoja metaboliczna podróż trwa dalej.</p>
          </div>

          <form class="login-form" @submit.prevent="submit" novalidate>
            <div class="form-group">
              <label class="form-label" for="email">Adres e-mail</label>
              <div class="input-wrapper">
                <i class="fa-regular fa-envelope input-icon"></i>
                <input
                  id="email" v-model="form.email" type="email" class="form-input"
                  :class="{ 'has-error': fieldError(error, 'email') }"
                  placeholder="vitality@mealcore.com" autocomplete="email" maxlength="255"
                />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="password">Hasło</label>
              <div class="input-wrapper">
                <i class="fa-solid fa-lock input-icon"></i>
                <input
                  id="password" v-model="form.password" type="password" class="form-input"
                  placeholder="••••••••" autocomplete="current-password" maxlength="200"
                />
              </div>
            </div>

            <div v-if="error" class="login-error">{{ error.message }}</div>

            <button type="submit" class="signin-btn" :disabled="loading">
              <span class="signin-text">{{ loading ? 'Logowanie…' : 'Zaloguj się' }}</span>
              <i class="fa-solid fa-arrow-right" style="color:white"></i>
            </button>
          </form>

          <div class="card-footer">
            <p class="signup-text">
              Nie masz konta?&nbsp;<RouterLink to="/register" class="signup-link">Zarejestruj się</RouterLink>
            </p>
          </div>
        </div>
      </div>
    </main>

    <footer class="login-footer">
      <span class="footer-copyright">© 2026 MealCore</span>
    </footer>
  </div>
</template>
