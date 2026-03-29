import { createRouter, createWebHashHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/pages/DashboardPage.vue'),
  },
  {
    path: '/generate-keys',
    name: 'generate-keys',
    component: () => import('@/pages/GenerateKeysPage.vue'),
  },
  {
    path: '/generate-license',
    name: 'generate-license',
    component: () => import('@/pages/GenerateLicensePage.vue'),
  },
  {
    path: '/domains',
    name: 'domains',
    component: () => import('@/pages/DomainRegistryPage.vue'),
  },
  {
    path: '/audit-log',
    name: 'audit-log',
    component: () => import('@/pages/AuditLogPage.vue'),
  },
  {
    path: '/guide',
    name: 'guide',
    component: () => import('@/pages/GuidePage.vue'),
  },
  {
    path: '/system',
    name: 'system',
    component: () => import('@/pages/SystemPage.vue'),
  },
  {
    path: '/about',
    name: 'about',
    component: () => import('@/pages/AboutPage.vue'),
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

export default router
