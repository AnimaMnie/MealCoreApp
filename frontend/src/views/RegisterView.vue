<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, fieldError } from '../api'
import { auth } from '../auth'

const router = useRouter()
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref(null)
const loading = ref(false)

async function submit() {
  error.value = null
  loading.value = true
  try {
    const res = await api.post('/auth/register', form)
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
  <div class="login-body scrollable">
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
            <div class="login-icon-pill"><i class="fa-solid fa-seedling" style="color:#874E00"></i></div>
            <h1 class="login-title">Załóż konto</h1>
            <p class="login-subtitle">Zacznij prowadzić swój dziennik posiłków.</p>
          </div>

          <form class="login-form" @submit.prevent="submit" novalidate>
            <div class="form-group">
              <label class="form-label" for="name">Nazwa użytkownika</label>
              <div class="input-wrapper">
                <i class="fa-regular fa-user input-icon"></i>
                <input id="name" v-model="form.name" class="form-input" :class="{ 'has-error': fieldError(error, 'name') }" placeholder="np. alex" maxlength="50" />
              </div>
              <div class="field-error">{{ fieldError(error, 'name') }}</div>
            </div>

            <div class="form-group">
              <label class="form-label" for="email">Adres e-mail</label>
              <div class="input-wrapper">
                <i class="fa-regular fa-envelope input-icon"></i>
                <input id="email" v-model="form.email" type="email" class="form-input" :class="{ 'has-error': fieldError(error, 'email') }" placeholder="vitality@mealcore.com" maxlength="255" />
              </div>
              <div class="field-error">{{ fieldError(error, 'email') }}</div>
            </div>

            <div class="form-group">
              <label class="form-label" for="password">Hasło (min. 8 znaków, litera i cyfra)</label>
              <div class="input-wrapper">
                <i class="fa-solid fa-lock input-icon"></i>
                <input id="password" v-model="form.password" type="password" class="form-input" :class="{ 'has-error': fieldError(error, 'password') }" placeholder="••••••••" autocomplete="new-password" />
              </div>
              <div class="field-error">{{ fieldError(error, 'password') }}</div>
            </div>

            <div class="form-group">
              <label class="form-label" for="password2">Powtórz hasło</label>
              <div class="input-wrapper">
                <i class="fa-solid fa-lock input-icon"></i>
                <input id="password2" v-model="form.password_confirmation" type="password" class="form-input" placeholder="••••••••" autocomplete="new-password" />
              </div>
            </div>

            <div v-if="error && !Object.keys(error.errors).length" class="login-error">{{ error.message }}</div>

            <button type="submit" class="signin-btn" :disabled="loading">
              <span class="signin-text">{{ loading ? 'Tworzenie konta…' : 'Zarejestruj się' }}</span>
              <i class="fa-solid fa-arrow-right" style="color:white"></i>
            </button>
          </form>

          <div class="card-footer">
            <p class="signup-text">
              Masz już konto?&nbsp;<RouterLink to="/login" class="signup-link">Zaloguj się</RouterLink>
            </p>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
