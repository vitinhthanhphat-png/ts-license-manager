<template>
  <q-page padding>
    <div class="page-header">
      <div class="row items-center justify-between">
        <div>
          <h1>Domain Registry</h1>
          <p>All domains with issued licenses</p>
        </div>
        <div class="row q-gutter-sm">
          <q-btn
            outline color="primary"
            icon="refresh"
            label="Refresh"
            @click="loadLicenses"
            :loading="store.loading"
          />
          <q-btn
            color="primary"
            icon="add"
            label="New License"
            @click="$router.push('/generate-license')"
          />
        </div>
      </div>
    </div>

    <!-- Filters -->
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="row q-col-gutter-sm items-end">
          <div class="col-12 col-sm-4">
            <q-input
              v-model="searchDomain"
              label="Search Domain"
              outlined dense
              debounce="300"
              @update:model-value="loadLicenses"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
              <template v-slot:append v-if="searchDomain">
                <q-icon name="close" class="cursor-pointer" @click="searchDomain = ''; loadLicenses()" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-sm-3">
            <q-select
              v-model="filterStatus"
              :options="statusOptions"
              label="Status"
              outlined dense
              emit-value
              map-options
              @update:model-value="loadLicenses"
            />
          </div>
          <div class="col-12 col-sm-3">
            <q-select
              v-model="filterType"
              :options="typeOptions"
              label="Type"
              outlined dense
              emit-value
              map-options
              @update:model-value="loadLicenses"
            />
          </div>
          <div class="col-12 col-sm-2">
            <q-btn
              flat color="grey"
              label="Clear"
              @click="clearFilters"
              class="full-width"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Table -->
    <q-card>
      <q-table
        :rows="store.licenses"
        :columns="columns"
        row-key="id"
        flat
        :loading="store.loading"
        v-model:pagination="pagination"
        @request="onRequest"
        :rows-per-page-options="[10, 20, 50, 100]"
      >
        <template v-slot:body-cell-domain="props">
          <q-td :props="props">
            <div class="text-weight-bold">{{ props.value }}</div>
          </q-td>
        </template>

        <template v-slot:body-cell-license_type="props">
          <q-td :props="props">
            <q-badge :color="typeColor(props.value)">
              {{ props.value }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-status="props">
          <q-td :props="props">
            <q-badge
              :class="`status-${props.value}`"
              text-color=""
              class="q-pa-xs q-px-sm"
              style="font-weight: 600"
            >
              {{ props.value }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-expires_at="props">
          <q-td :props="props">
            <span v-if="props.value">{{ props.value }}</span>
            <q-badge v-else color="primary" outline>Lifetime</q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn flat round size="sm" icon="content_copy" @click="copyActivationCode(props.row)">
              <q-tooltip>Copy Activation Code</q-tooltip>
            </q-btn>
            <q-btn flat round size="sm" icon="verified" color="primary" @click="verifyLicense(props.row)">
              <q-tooltip>Verify License</q-tooltip>
            </q-btn>
            <q-btn
              v-if="props.row.status === 'active'"
              flat round size="sm" icon="lock" color="warning"
              @click="confirmToggleStatus(props.row, 'locked')"
            >
              <q-tooltip>Lock License</q-tooltip>
            </q-btn>
            <q-btn
              v-else-if="props.row.status === 'locked' || props.row.status === 'revoked'"
              flat round size="sm" icon="lock_open" color="positive"
              @click="confirmToggleStatus(props.row, 'active')"
            >
              <q-tooltip>Unlock License</q-tooltip>
            </q-btn>
            <q-btn
              flat round size="sm" icon="delete" color="negative"
              @click="confirmDelete(props.row)"
            >
              <q-tooltip>Delete</q-tooltip>
            </q-btn>
          </q-td>
        </template>

        <template v-slot:no-data>
          <div class="empty-state full-width">
            <q-icon name="dns" />
            <h3>No licenses found</h3>
            <p>Generate your first license to see it here</p>
          </div>
        </template>
      </q-table>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar, copyToClipboard } from 'quasar'
import { useLicenseStore } from '@/stores/licenses'

const $q = useQuasar()
const store = useLicenseStore()

const searchDomain = ref('')
const filterStatus = ref('')
const filterType = ref('')

const pagination = ref({
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0,
})

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Locked/Revoked', value: 'locked' },
  { label: 'Expired', value: 'expired' },
]

const typeOptions = [
  { label: 'All Types', value: '' },
  { label: 'Lifetime', value: 'lifetime' },
  { label: 'Yearly', value: 'yearly' },
  { label: 'Trial', value: 'trial' },
]

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true, style: 'width: 60px' },
  { name: 'domain', label: 'Domain', field: 'domain', align: 'left', sortable: true },
  { name: 'license_type', label: 'Type', field: 'license_type', align: 'center' },
  { name: 'customer_name', label: 'Customer', field: 'customer_name', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'center' },
  { name: 'created_at', label: 'Created', field: 'created_at', align: 'left', sortable: true },
  { name: 'expires_at', label: 'Expires', field: 'expires_at', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'id', align: 'center', style: 'width: 130px' },
]

function typeColor(type) {
  return { lifetime: 'primary', yearly: 'positive', trial: 'warning' }[type] || 'grey'
}

async function loadLicenses() {
  await store.fetchLicenses({
    domain: searchDomain.value,
    status: filterStatus.value,
    type: filterType.value,
    page: pagination.value.page,
    per_page: pagination.value.rowsPerPage,
  })
  pagination.value.rowsNumber = store.total
}

function onRequest(props) {
  pagination.value.page = props.pagination.page
  pagination.value.rowsPerPage = props.pagination.rowsPerPage
  loadLicenses()
}

function clearFilters() {
  searchDomain.value = ''
  filterStatus.value = ''
  filterType.value = ''
  pagination.value.page = 1
  loadLicenses()
}

async function copyActivationCode(row) {
  if (!row.activation_code) {
    $q.notify({ type: 'warning', message: 'No activation code available' })
    return
  }
  await copyToClipboard(row.activation_code)
  $q.notify({ type: 'positive', message: `Activation code for ${row.domain} copied!` })
}

async function verifyLicense(row) {
  try {
    const result = await store.verifyLicense(row.id)
    $q.dialog({
      title: result.valid ? '✅ License Valid' : '❌ License Invalid',
      message: result.valid
        ? `License for ${row.domain} is valid.\nType: ${result.data?.type}\nExpires: ${result.data?.expires_at ? new Date(result.data.expires_at * 1000).toLocaleDateString() : 'Never'}`
        : `Verification failed: ${result.error}`,
      ok: true,
    })
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Verification failed' })
  }
}

function confirmDelete(row) {
  $q.dialog({
    title: 'Delete License',
    message: `Are you sure you want to delete the license for <strong>${row.domain}</strong>?<br>This action cannot be undone.`,
    html: true,
    cancel: true,
    persistent: true,
    color: 'negative',
  }).onOk(async () => {
    try {
      await store.deleteLicense(row.id)
      $q.notify({ type: 'positive', message: `License for ${row.domain} deleted` })
    } catch (e) {
      $q.notify({ type: 'negative', message: 'Failed to delete license' })
    }
  })
}

function confirmToggleStatus(row, newStatus) {
  const actionName = newStatus === 'active' ? 'Unlock' : 'Lock'
  const actionColor = newStatus === 'active' ? 'positive' : 'warning'
  
  $q.dialog({
    title: `${actionName} License`,
    message: `Are you sure you want to <strong>${actionName.toLowerCase()}</strong> the license for <strong>${row.domain}</strong>?`,
    html: true,
    cancel: true,
    persistent: true,
    color: actionColor,
  }).onOk(async () => {
    try {
      await store.updateStatus(row.id, newStatus)
      $q.notify({ type: 'positive', message: `License for ${row.domain} is now ${newStatus}` })
    } catch (e) {
      $q.notify({ type: 'negative', message: `Failed to ${actionName.toLowerCase()} license` })
    }
  })
}

onMounted(() => {
  loadLicenses()
})
</script>
