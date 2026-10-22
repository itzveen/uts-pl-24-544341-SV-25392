<script setup>
import { watch } from 'vue'
import { useRouter } from 'vue-router'
import http from './api/http'
import { auth, clearSession } from './auth'

const router = useRouter()

// Kapan pun token hilang (logout atau 401), kembali ke halaman login.
watch(
  () => auth.token,
  (token) => {
    if (!token) router.push({ name: 'login' })
  },
)

async function logout() {
  try {
    await http.post('/logout')
  } catch {
    // Token mungkin sudah tidak valid; sesi lokal tetap dibersihkan.
  } finally {
    clearSession()
  }
}
</script>

<template>
  <header class="topbar">
    <div class="brand">Booking Studio dan Lapangan</div>
    <div v-if="auth.token" class="topbar-right">
      <span class="user">{{ auth.user?.email }}</span>
      <button class="btn btn-outline" @click="logout">Logout</button>
    </div>
  </header>

  <main class="container">
    <router-view />
  </main>
</template>