import { createRouter, createWebHistory } from 'vue-router'
import onboard from '../views/onboard.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: onboard
    },

  ]
})

export default router
