import { createRouter, createWebHashHistory } from 'vue-router'
import Dashboard from '../views/dashboard.vue'
import CSS from '../views/pages/unused-css/index.vue'
import javascript from '../views/pages/java-script.vue'
import assetManager from '../views/pages/asset-manager.vue'
import removeUnusedCss from '../views/pages/unused-css/remove-unused-css.vue'
import generalSettings from '../views/pages/general-settings.vue'


const router = createRouter({

  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: Dashboard
    },
    {
      path: '/unused-css',
      name: 'unused-css',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: CSS
    },

    {
      path: '/java-script',
      name: 'java-script',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: javascript
    },
    {
      path: '/speed-monitoring',
      name: 'speed-monitoring',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/pages/speed-monitoring.vue')
    },
    {
      path: '/asset-manager',
      name: 'asset-manager',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: assetManager
    },
    {
      path: '/font-optimization',
      name: 'font-optimization',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/pages/font-optimization.vue')
    },
    {
      path: '/page-optimizer',
      name: 'page-optimizer',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/pages/page-optimizer.vue')
    },
    {
      path: '/remove-unused-css',
      name: 'remove-unused-css',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: removeUnusedCss
    },
    {
      path: '/general-settings',
      name: 'general-settings',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: generalSettings
    },
  ]
})

export default router
