<template>
  <q-page padding>
    <div class="page-header">
      <h1>Audit Log</h1>
      <p>History of all key and license operations</p>
    </div>

    <!-- Filters -->
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="row q-col-gutter-sm items-end">
          <div class="col-12 col-sm-4">
            <q-select
              v-model="filterAction"
              :options="actionOptions"
              label="Filter by Action"
              outlined dense
              emit-value
              map-options
              @update:model-value="loadLog"
            />
          </div>
          <div class="col-auto">
            <q-btn
              outline color="primary"
              icon="refresh"
              label="Refresh"
              @click="loadLog"
              :loading="loading"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Log Table -->
    <q-card>
      <q-table
        :rows="logItems"
        :columns="columns"
        row-key="id"
        flat
        :loading="loading"
        v-model:pagination="pagination"
        @request="onRequest"
        :rows-per-page-options="[20, 50, 100]"
      >
        <template v-slot:body-cell-action="props">
          <q-td :props="props">
            <q-badge :color="actionColor(props.value)" outline>
              <q-icon :name="actionIcon(props.value)" size="xs" class="q-mr-xs" />
              {{ props.value.replace(/_/g, ' ') }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-details="props">
          <q-td :props="props">
            <template v-if="props.value && typeof props.value === 'object'">
              <div v-for="(val, key) in props.value" :key="key" class="text-caption">
                <strong>{{ key }}:</strong> {{ val }}
              </div>
            </template>
            <span v-else class="text-grey">—</span>
          </q-td>
        </template>

        <template v-slot:body-cell-user_name="props">
          <q-td :props="props">
            <q-chip v-if="props.value" dense size="sm" icon="person">
              {{ props.value }}
            </q-chip>
            <span v-else class="text-grey">System</span>
          </q-td>
        </template>

        <template v-slot:no-data>
          <div class="empty-state full-width">
            <q-icon name="history" />
            <h3>No audit log entries</h3>
            <p>Actions will appear here as they are performed</p>
          </div>
        </template>
      </q-table>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { auditApi } from '@/api'

const logItems = ref([])
const loading = ref(false)
const filterAction = ref('')

const pagination = ref({
  page: 1,
  rowsPerPage: 50,
  rowsNumber: 0,
})

const actionOptions = [
  { label: 'All Actions', value: '' },
  { label: 'Key Generated', value: 'key_generated' },
  { label: 'Key Imported', value: 'key_imported' },
  { label: 'Key Deleted', value: 'key_deleted' },
  { label: 'License Created', value: 'license_created' },
  { label: 'License Revoked', value: 'license_revoked' },
]

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', style: 'width: 60px' },
  { name: 'action', label: 'Action', field: 'action', align: 'left' },
  { name: 'entity_type', label: 'Entity', field: 'entity_type', align: 'left' },
  { name: 'details', label: 'Details', field: 'details', align: 'left', style: 'max-width: 300px' },
  { name: 'user_name', label: 'User', field: 'user_name', align: 'left' },
  { name: 'ip_address', label: 'IP', field: 'ip_address', align: 'left' },
  { name: 'created_at', label: 'Date', field: 'created_at', align: 'left', sortable: true },
]

function actionColor(action) {
  if (action?.includes('generated') || action?.includes('created')) return 'positive'
  if (action?.includes('deleted') || action?.includes('revoked')) return 'negative'
  if (action?.includes('imported')) return 'info'
  return 'grey'
}

function actionIcon(action) {
  if (action?.includes('key')) return 'vpn_key'
  if (action?.includes('license')) return 'card_giftcard'
  return 'history'
}

async function loadLog() {
  loading.value = true
  try {
    const { data } = await auditApi.getLog({
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      action: filterAction.value || undefined,
    })
    logItems.value = data.items || []
    pagination.value.rowsNumber = data.total || 0
  } catch (e) {
    console.error('Failed to load audit log', e)
  } finally {
    loading.value = false
  }
}

function onRequest(props) {
  pagination.value.page = props.pagination.page
  pagination.value.rowsPerPage = props.pagination.rowsPerPage
  loadLog()
}

onMounted(() => {
  loadLog()
})
</script>
