import { reactive } from 'vue'

const TOKEN_KEY = 'booking_token'
const USER_KEY = 'booking_user'

function readUser() {
  try {
    return JSON.parse(localStorage.getItem(USER_KEY))
  } catch {
    return null
  }
}

export const auth = reactive({
  token: localStorage.getItem(TOKEN_KEY),
  user: readUser(),
})

export function setSession(token, user) {
  auth.token = token
  auth.user = user
  localStorage.setItem(TOKEN_KEY, token)
  localStorage.setItem(USER_KEY, JSON.stringify(user))
}

export function clearSession() {
  auth.token = null
  auth.user = null
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(USER_KEY)
}