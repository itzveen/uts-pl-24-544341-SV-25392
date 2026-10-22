import axios from 'axios'
import { auth, clearSession } from '../auth'

// Alamat API TIDAK ditulis mati di komponen: diambil dari variabel lingkungan VITE_API_URL.
// Jika tidak diisi, dipakai path relatif "/api" (dipakai saat nanti di belakang Nginx di Docker).
const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: { Accept: 'application/json' },
})

// Setiap request berikutnya otomatis membawa token.
http.interceptors.request.use((config) => {
  if (auth.token) {
    config.headers.Authorization = `Bearer ${auth.token}`
  }
  return config
})

// Token tidak valid atau kedaluwarsa: hapus sesi, App.vue akan mengarahkan ke halaman login.
http.interceptors.response.use(
  (response) => response,
  (error) => {
    const isLoginRequest = error.config?.url?.includes('/login')
    if (error.response?.status === 401 && !isLoginRequest) {
      clearSession()
    }
    return Promise.reject(error)
  },
)

export default http