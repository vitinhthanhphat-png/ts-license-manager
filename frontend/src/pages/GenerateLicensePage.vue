<template>
  <q-page padding>
    <div class="page-header">
      <h1>Generate License</h1>
      <p>Create a signed activation code for a domain</p>
    </div>

    <!-- No Key Warning -->
    <q-banner v-if="!keyStore.hasActiveKey" class="bg-negative text-white q-mb-lg rounded-borders">
      <template v-slot:avatar>
        <q-icon name="warning" />
      </template>
      No active key pair. Generate one first before creating licenses.
      <template v-slot:action>
        <q-btn flat label="Go to Key Management" @click="$router.push('/generate-keys')" />
      </template>
    </q-banner>

    <div class="row q-col-gutter-lg">
      <!-- Form -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6 q-mb-md">
              <q-icon name="edit_note" class="q-mr-sm" />
              License Details
            </div>

            <q-form @submit.prevent="handleGenerate" class="q-gutter-y-md">
              <q-input
                v-model="form.domain"
                label="Domain *"
                placeholder="example.com"
                outlined
                :rules="[val => !!val || 'Domain is required']"
                hint="The domain this license will be locked to"
              >
                <template v-slot:prepend>
                  <q-icon name="dns" />
                </template>
              </q-input>

              <q-select
                v-model="form.type"
                :options="licenseTypes"
                label="License Type"
                outlined
                emit-value
                map-options
              >
                <template v-slot:prepend>
                  <q-icon name="card_giftcard" />
                </template>
              </q-select>

              <q-input
                v-model="form.customer"
                label="Customer Name"
                placeholder="Nguyen Van A"
                outlined
              >
                <template v-slot:prepend>
                  <q-icon name="person" />
                </template>
              </q-input>




              <q-input
                v-if="form.type !== 'lifetime'"
                v-model="form.expires"
                label="Custom Expiry Date"
                outlined
                type="date"
                hint="Override default expiry based on license type"
              >
                <template v-slot:prepend>
                  <q-icon name="event" />
                </template>
              </q-input>

              <q-input
                v-model="form.notes"
                label="Notes (Optional)"
                outlined
                type="textarea"
                rows="2"
              >
                <template v-slot:prepend>
                  <q-icon name="note" />
                </template>
              </q-input>

              <q-btn
                type="submit"
                color="primary"
                label="Generate License"
                icon="vpn_key"
                class="full-width"
                size="lg"
                :loading="licenseStore.loading"
                :disable="!keyStore.hasActiveKey"
              />
            </q-form>
          </q-card-section>
        </q-card>
      </div>

      <!-- Result -->
      <div class="col-12 col-md-6">
        <q-card v-if="lastResult" style="border-left: 4px solid #34a853">
          <q-card-section>
            <div class="row items-center q-mb-md">
              <q-icon name="check_circle" color="positive" size="md" class="q-mr-sm" />
              <div class="text-h6">License Generated!</div>
            </div>

            <q-list dense separator>
              <q-item>
                <q-item-section>Domain</q-item-section>
                <q-item-section side class="text-weight-bold">{{ lastResult.data.domain }}</q-item-section>
              </q-item>
              <q-item>
                <q-item-section>Type</q-item-section>
                <q-item-section side>
                  <q-badge :color="typeColor(lastResult.data.type)">{{ lastResult.data.type }}</q-badge>
                </q-item-section>
              </q-item>
              <q-item v-if="lastResult.data.customer">
                <q-item-section>Customer</q-item-section>
                <q-item-section side>{{ lastResult.data.customer }}</q-item-section>
              </q-item>
              <q-item>
                <q-item-section>Created</q-item-section>
                <q-item-section side>{{ lastResult.data.created_at }}</q-item-section>
              </q-item>
              <q-item>
                <q-item-section>Expires</q-item-section>
                <q-item-section side>{{ lastResult.data.expires_at || 'Never (lifetime)' }}</q-item-section>
              </q-item>
            </q-list>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div class="text-subtitle2 q-mb-sm">Activation Code</div>
            <div class="code-display">
              {{ lastResult.data.activation_code }}
              <q-btn
                class="copy-btn"
                flat round size="sm"
                icon="content_copy"
                color="white"
                @click="copyCode"
              >
                <q-tooltip>Copy to clipboard</q-tooltip>
              </q-btn>
            </div>
          </q-card-section>

          <q-card-section class="bg-blue-1">
            <div class="text-subtitle2 q-mb-xs">
              <q-icon name="info" class="q-mr-xs" /> Instructions for Customer
            </div>
            <ol class="q-ma-none q-pl-md text-body2">
              <li>Go to <strong>WP Admin → TS Wallet → Settings → License</strong></li>
              <li>Paste the activation code in the "Offline Activation Code" field</li>
              <li>Click <strong>"Activate Offline"</strong></li>
            </ol>
          </q-card-section>
        </q-card>

        <!-- Empty state -->
        <q-card v-else class="bg-grey-1">
          <q-card-section class="text-center q-pa-xl">
            <q-icon name="receipt_long" size="60px" color="grey-4" />
            <div class="text-h6 text-grey-6 q-mt-md">No license generated yet</div>
            <div class="text-body2 text-grey-5">Fill in the form and click Generate</div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useQuasar, copyToClipboard } from 'quasar'
import { useLicenseStore } from '@/stores/licenses'
import { useKeyStore } from '@/stores/keys'
import { useDashboardStore } from '@/stores/dashboard'

const $q = useQuasar()
const licenseStore = useLicenseStore()
const keyStore = useKeyStore()
const dashboardStore = useDashboardStore()

const form = reactive({
  domain: '',
  type: 'lifetime',
  customer: '',


  expires: '',
  notes: '',
})

const lastResult = ref(null)

const licenseTypes = [
  { label: '♾️ Lifetime — Never expires', value: 'lifetime' },
  { label: '📅 Yearly — 365 days', value: 'yearly' },
  { label: '⏱️ Trial — 30 days', value: 'trial' },
]



function typeColor(type) {
  return { lifetime: 'primary', yearly: 'positive', trial: 'warning' }[type] || 'grey'
}

async function handleGenerate() {
  if (!form.domain) {
    $q.notify({ type: 'negative', message: 'Domain is required' })
    return
  }

  try {
    const result = await licenseStore.generateLicense({
      domain: form.domain,
      type: form.type,
      customer: form.customer || undefined,


      expires: form.expires || undefined,
      notes: form.notes || undefined,
    })
    lastResult.value = result
    dashboardStore.fetchStats()
    $q.notify({ type: 'positive', message: `License generated for ${form.domain}` })
  } catch (e) {
    $q.notify({ type: 'negative', message: e.response?.data?.error || 'Failed to generate license' })
  }
}

async function copyCode() {
  if (!lastResult.value?.data?.activation_code) return
  await copyToClipboard(lastResult.value.data.activation_code)
  $q.notify({ type: 'positive', message: 'Activation code copied!' })
}
</script>
