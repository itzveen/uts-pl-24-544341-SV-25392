<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import http from '../api/http'
import { setSession } from '../auth'

const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const fieldErrors = ref({})

async function submit() {
  loading.value = true
  error.value = ''
  fieldErrors.value = {}

  try {
    const { data } = await http.post('/login', {
      email: email.value,
      password: password.value,
    })
    setSession(data.token, data.user)
    router.push({ name: 'bookings' })
  } catch (e) {
    if (e.response?.status === 422) {
      fieldErrors.value = e.response.data.errors || {}
    } else if (e.response?.status === 401) {
      error.value = e.response.data.message
    } else {
      error.value = 'Tidak dapat terhubung ke server.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-wrap">
    <div class="card login-card">
      <h1>Masuk</h1>
      <p class="muted">Silakan login untuk mengelola booking.</p>

      <p v-if="error" class="alert alert-error">{{ error }}</p>

      <form @submit.prevent="submit" novalidate>
        <div class="field">
          <label for="email">Email</label>
          <input id="email" v-model="email" type="email" autocomplete="username" />
          <small v-if="fieldErrors.email" class="error">{{ fieldErrors.email[0] }}</small>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input id="password" v-model="password" type="password" autocomplete="current-password" />
          <small v-if="fieldErrors.password" class="error">{{ fieldErrors.password[0] }}</small>
        </div>

        <button class="btn btn-primary btn-block" type="submit" :disabled="loading">
          {{ loading ? 'Memproses...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>