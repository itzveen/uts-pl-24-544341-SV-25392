import { createRouter, createWebHistory } from 'vue-router'
import { auth } from '../auth'
import LoginView from '../views/LoginView.vue'
import BookingsView from '../views/BookingsView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/bookings' },
    { path: '/login', name: 'login', component: LoginView, meta: { guest: true } },
    { path: '/bookings', name: 'bookings', component: BookingsView, meta: { requiresAuth: true } },
    { path: '/:pathMatch(.*)*', redirect: '/bookings' },
  ],
})

router.beforeEach((to) => {
  if (to.meta.requiresAuth && !auth.token) return { name: 'login' }
  if (to.meta.guest && auth.token) return { name: 'bookings' }
})

export default router