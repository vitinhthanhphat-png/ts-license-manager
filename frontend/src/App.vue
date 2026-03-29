<template>
  <q-layout view="hHh lpr lff" class="tslm-layout" :style="layoutStyle">
    <q-header class="tslm-header">
      <q-toolbar class="tslm-toolbar">
        <q-icon name="admin_panel_settings" size="sm" class="q-mr-sm" />
        <q-toolbar-title class="text-weight-bold tslm-title">
          TS License Manager
        </q-toolbar-title>
        <q-badge v-if="keyStore.hasActiveKey" color="positive" class="q-pa-xs q-px-sm">
          <q-icon name="vpn_key" size="xs" class="q-mr-xs" />
          Key Active
        </q-badge>
        <q-badge v-else color="negative" class="q-pa-xs q-px-sm">
          <q-icon name="warning" size="xs" class="q-mr-xs" />
          No Key
        </q-badge>
        <q-btn flat round dense icon="refresh" color="white" @click="refreshAll" :loading="loading" size="sm">
          <q-tooltip>Refresh Data</q-tooltip>
        </q-btn>
      </q-toolbar>

      <!-- Tab Navigation -->
      <div class="tslm-nav">
        <router-link
          v-for="nav in navItems"
          :key="nav.path"
          :to="nav.path"
          class="tslm-nav-item"
          :class="{ 'tslm-nav-active': isActive(nav.path) }"
        >
          <q-icon :name="nav.icon" size="xs" />
          <span>{{ nav.label }}</span>
        </router-link>
      </div>
    </q-header>

    <q-page-container>
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useKeyStore } from '@/stores/keys'
import { useDashboardStore } from '@/stores/dashboard'

const route = useRoute()
const keyStore = useKeyStore()
const dashboardStore = useDashboardStore()
const loading = ref(false)

// Force QLayout to NOT use fixed positioning
const layoutStyle = {
  position: 'relative',
  minHeight: 'auto',
}

const navItems = [
  { path: '/', icon: 'dashboard', label: 'Dashboard' },
  { path: '/generate-keys', icon: 'vpn_key', label: 'Keys' },
  { path: '/generate-license', icon: 'card_giftcard', label: 'Generate License' },
  { path: '/domains', icon: 'dns', label: 'Domains' },
  { path: '/audit-log', icon: 'history', label: 'Audit Log' },
  { path: '/guide', icon: 'menu_book', label: 'Guide' },
]

function isActive(path) {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

async function refreshAll() {
  loading.value = true
  try {
    await Promise.all([
      keyStore.fetchKeyStatus(),
      dashboardStore.fetchStats(),
    ])
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  keyStore.fetchKeyStatus()
  dashboardStore.fetchStats()
})
</script>
