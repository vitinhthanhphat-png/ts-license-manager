<template>
  <q-page padding>
    <div class="page-header">
      <h1>Guide</h1>
      <p>How to use TS License Manager for your plugins</p>
    </div>

    <!-- Quick Start -->
    <q-card class="q-mb-lg">
      <q-card-section>
        <div class="text-h6 q-mb-md">
          <q-icon name="rocket_launch" color="primary" class="q-mr-sm" />
          Quick Start
        </div>
        <q-stepper v-model="step" vertical animated color="primary" flat>
          <q-step :name="1" title="Generate RSA Key Pair" icon="vpn_key" :done="step > 1">
            <div class="text-body1">
              Go to <strong>Keys</strong> tab and click <q-badge color="primary" class="q-ml-xs q-mr-xs">+ Generate New Key Pair</q-badge>
              to create your first RSA-2048 key pair. This key signs all licenses.
            </div>
            <q-btn @click="step = 2" color="primary" label="Next" flat class="q-mt-sm" />
          </q-step>

          <q-step :name="2" title="Embed Public Key in Your Plugin" icon="code" :done="step > 2">
            <div class="text-body1">
              Copy the <strong>Public Key</strong> from the Keys page and embed it in your plugin's code.
              The client plugin uses this key to verify license signatures offline — no API call needed.
            </div>
            <div class="code-block q-my-sm">
              <code class="text-caption">
                define('MY_PLUGIN_PUBLIC_KEY', '-----BEGIN PUBLIC KEY----- ...');
              </code>
            </div>
            <q-btn @click="step = 3" color="primary" label="Next" flat class="q-mt-sm" />
          </q-step>

          <q-step :name="3" title="Generate License for Domain" icon="card_giftcard" :done="step > 3">
            <div class="text-body1">
              Go to <strong>Generate License</strong> tab, enter the customer's domain, select license type, and click Generate.
              An activation code will be created using your private key.
            </div>
            <q-btn @click="step = 4" color="primary" label="Next" flat class="q-mt-sm" />
          </q-step>

          <q-step :name="4" title="Customer Activates License" icon="check_circle">
            <div class="text-body1">
              Send the activation code to your customer. They paste it in their plugin settings to activate.
              The plugin verifies the code using the embedded public key — no server communication required.
            </div>
          </q-step>
        </q-stepper>
      </q-card-section>
    </q-card>

    <!-- Architecture -->
    <div class="row q-col-gutter-lg q-mb-lg">
      <div class="col-12 col-md-6">
        <q-card class="full-height">
          <q-card-section>
            <div class="text-h6 q-mb-md">
              <q-icon name="security" color="teal" class="q-mr-sm" />
              How It Works
            </div>
            <q-list separator>
              <q-item>
                <q-item-section avatar>
                  <q-icon name="key" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>RSA-2048 Asymmetric Signing</q-item-label>
                  <q-item-label caption>
                    Private key signs licenses on your server. Public key verifies on client sites.
                    Even if someone has the public key, they cannot forge licenses.
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section avatar>
                  <q-icon name="wifi_off" color="orange" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>100% Offline Verification</q-item-label>
                  <q-item-label caption>
                    Client plugins verify licenses locally — no API calls, no phone-home, no server dependency.
                  </q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section avatar>
                  <q-icon name="dns" color="indigo" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>Domain-Locked</q-item-label>
                  <q-item-label caption>
                    Each license is bound to a specific domain. The activation code includes the domain hash so it cannot be reused elsewhere.
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-6">
        <q-card class="full-height">
          <q-card-section>
            <div class="text-h6 q-mb-md">
              <q-icon name="integration_instructions" color="deep-purple" class="q-mr-sm" />
              Integration Snippet
            </div>
            <p class="text-body2 text-grey-7 q-mb-sm">
              Add this to your client plugin to verify licenses:
            </p>
            <div class="code-block">
              <pre class="text-caption q-ma-none"><code>// In your plugin's license checker
$public_key = get_option('my_plugin_public_key');
$license_data = json_decode(
  base64_decode($activation_code), true
);

// Step 1: Verify signature
$signature = base64_decode($license_data['sig']);
$payload = json_encode($license_data['data']);
$valid = openssl_verify(
  $payload, $signature, $public_key,
  OPENSSL_ALGO_SHA256
);
if ($valid !== 1) {
  return 'invalid_signature';
}

// Step 2: Verify domain (skip on localhost)
$data = $license_data['data'];
$current = parse_url(home_url(), PHP_URL_HOST);
$is_local = in_array($current, [
  'localhost', '127.0.0.1', '::1'
]) || str_ends_with($current, '.local');

if (!$is_local && $data['domain'] !== $current) {
  return 'domain_mismatch';
}

// Step 3: Check expiry (lifetime = no expiry)
if (!empty($data['expires_at'])) {
  $expires = strtotime($data['expires_at']);
  if (time() > $expires) {
    return 'license_expired';
  }
}

// Step 4: License type info
// $data['type'] = 'lifetime' | 'yearly' | 'trial'
// $data['created_at'] = '2026-01-15 10:00:00'
// $data['expires_at'] = null (lifetime) or date

return 'active'; // ✅ License valid!</code></pre>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- FAQ -->
    <q-card>
      <q-card-section>
        <div class="text-h6 q-mb-md">
          <q-icon name="quiz" color="amber-9" class="q-mr-sm" />
          Frequently Asked Questions
        </div>
        <q-list separator>
          <q-expansion-item
            v-for="faq in faqs"
            :key="faq.q"
            :label="faq.q"
            header-class="text-weight-medium"
            expand-icon-class="text-primary"
          >
            <q-card>
              <q-card-section class="text-body2 text-grey-8">
                {{ faq.a }}
              </q-card-section>
            </q-card>
          </q-expansion-item>
        </q-list>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref } from 'vue'

const step = ref(1)

const faqs = [
  {
    q: 'Can I use this for multiple plugins?',
    a: 'Yes! TS License Manager is designed as a universal license server. Generate different licenses for different plugins using the same key pair. Differentiate them via the "Notes" field or customer name.',
  },
  {
    q: 'What happens if I lose the private key?',
    a: 'All licenses signed with that key become unverifiable if you change the public key in your plugins. Keep backups! Keys are stored in wp-content/uploads/ts-license-keys/ — back up this directory regularly.',
  },
  {
    q: 'Can customers share activation codes?',
    a: 'Activation codes are domain-locked. A code generated for domain-a.com will fail verification on domain-b.com. This prevents casual sharing.',
  },
  {
    q: 'Do I need an API endpoint for verification?',
    a: 'No. The entire verification is done offline using RSA public key cryptography. The client plugin only needs the public key embedded in its code.',
  },
  {
    q: 'What license types are available?',
    a: 'Three types: Lifetime (never expires), Yearly (365 days), and Trial (30 days). You can also set a custom expiry date for any type.',
  },
  {
    q: 'How do I revoke a license?',
    a: 'Go to the Domains tab, find the domain, and click the revoke button. Note: revocation only works if your client plugin checks a revocation list, since offline verification cannot be remotely disabled.',
  },
]
</script>

<style scoped>
.code-block {
  background: #1e1e2e;
  color: #a6e3a1;
  border-radius: 8px;
  padding: 16px;
  overflow-x: auto;
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
}

.code-block code {
  color: #a6e3a1;
}

.code-block pre {
  white-space: pre-wrap;
  word-break: break-word;
}
</style>
