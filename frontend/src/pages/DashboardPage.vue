<template>
  <q-page padding>
    <div class="page-header">
      <h1>Dashboard</h1>
      <p>Overview of your license management system</p>
    </div>

    <!-- Key Status Alert -->
    <q-banner v-if="!keyStore.hasActiveKey" class="bg-negative text-white q-mb-lg rounded-borders">
      <template v-slot:avatar>
        <q-icon name="warning" />
      </template>
      <strong>No active key pair!</strong> You need to generate an RSA key pair before issuing licenses.
      <template v-slot:action>
        <q-btn flat label="Generate Key" @click="$router.push('/generate-keys')" />
      </template>
    </q-banner>

    <!-- Stats Cards -->
    <div v-if="stats" class="row q-col-gutter-md q-mb-lg justify-start">
      <div class="col-12 col-sm-4" v-for="card in statCards" :key="card.label">
        <q-card class="stat-card" :style="{ borderLeft: `4px solid ${card.color}` }">
          <q-card-section>
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="stat-value" :style="{ color: card.color }">{{ card.value }}</div>
                <div class="stat-label q-mt-xs">{{ card.label }}</div>
              </div>
              <div class="col-auto">
                <q-icon :name="card.icon" size="40px" :color="card.qColor" class="q-ml-md" style="opacity: 0.3" />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Secondary Stats -->
    <div v-if="stats" class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6 q-mb-md">
              <q-icon name="pie_chart" class="q-mr-sm" />
              License Types
            </div>
            <div class="row q-col-gutter-sm">
              <div class="col-4" v-for="type in typeCards" :key="type.label">
                <div class="text-center q-pa-md rounded-borders" :class="type.bgClass">
                  <div class="text-h4 text-weight-bold">{{ type.value }}</div>
                  <div class="text-caption text-uppercase">{{ type.label }}</div>
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6 q-mb-md">
              <q-icon name="info" class="q-mr-sm" />
              System Status
            </div>
            <q-list separator>
              <q-item>
                <q-item-section avatar>
                  <q-icon :name="stats.has_active_key ? 'check_circle' : 'cancel'"
                          :color="stats.has_active_key ? 'positive' : 'negative'" />
                </q-item-section>
                <q-item-section>RSA Key Pair</q-item-section>
                <q-item-section side>
                  <q-badge :color="stats.has_active_key ? 'positive' : 'negative'">
                    {{ stats.has_active_key ? 'Active' : 'Missing' }}
                  </q-badge>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section avatar>
                  <q-icon name="dns" color="primary" />
                </q-item-section>
                <q-item-section>Unique Domains</q-item-section>
                <q-item-section side>
                  <q-badge color="primary">{{ stats.unique_domains }}</q-badge>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section avatar>
                  <q-icon name="schedule" color="accent" />
                </q-item-section>
                <q-item-section>Created Last 30 Days</q-item-section>
                <q-item-section side>
                  <q-badge color="accent" text-color="dark">{{ stats.recent_30d }}</q-badge>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="store.loading" class="text-center q-pa-xl">
      <q-spinner-dots size="40px" color="primary" />
      <div class="q-mt-sm text-grey">Loading dashboard...</div>
    </div>
  </q-page>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { useKeyStore } from '@/stores/keys'

const store = useDashboardStore()
const keyStore = useKeyStore()
const stats = computed(() => store.stats)

const statCards = computed(() => {
  if (!stats.value) return []
  return [
    { label: 'Total Licenses', value: stats.value.total, icon: 'confirmation_number', color: '#1a73e8', qColor: 'primary' },
    { label: 'Active', value: stats.value.active, icon: 'check_circle', color: '#34a853', qColor: 'positive' },
    { label: 'Expired', value: stats.value.expired, icon: 'timer_off', color: '#fbbc04', qColor: 'warning' },
  ]
})

const typeCards = computed(() => {
  if (!stats.value) return []
  return [
    { label: 'Lifetime', value: stats.value.lifetime, bgClass: 'bg-blue-1 text-blue-9' },
    { label: 'Yearly', value: stats.value.yearly, bgClass: 'bg-green-1 text-green-9' },
    { label: 'Trial', value: stats.value.trial, bgClass: 'bg-orange-1 text-orange-9' },
  ]
})

onMounted(() => {
  store.fetchStats()
})
</script>
