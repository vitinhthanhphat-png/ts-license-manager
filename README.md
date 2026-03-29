# TS License Manager

<p align="center">
  <strong>🔐 Self-hosted WordPress License Server with RSA-2048 Signing</strong>
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#how-it-works">How It Works</a> •
  <a href="#installation">Installation</a> •
  <a href="#quick-start">Quick Start</a> •
  <a href="#integration">Integration</a> •
  <a href="#faq">FAQ</a>
</p>

---

## Overview

**TS License Manager** is a self-hosted WordPress plugin that lets you issue, sign, and manage software licenses using **RSA-2048 asymmetric cryptography**. Designed as a **universal license server** — use it to protect any WordPress plugin or theme you build.

No SaaS subscription. No external API dependency. 100% offline verification on client sites.

## Features

- 🔑 **RSA-2048 Key Management** — Generate and rotate key pairs from the admin UI
- 📜 **License Generation** — Issue domain-locked licenses with lifetime, yearly, or trial types
- 🌐 **Domain Registry** — Track which domains have issued licenses and delete them if necessary
- 📊 **Dashboard** — Overview of total licenses, active domains, and key status
- 📋 **Audit Log** — Complete history of all license operations
- 📖 **Built-in Guide** — Integration snippets and step-by-step instructions
- ✈️ **100% Offline Verification** — Client plugins verify licenses locally, no phone-home
- 🛡️ **Tamper-proof** — RSA signatures cannot be forged without the private key

## How It Works

```
┌──────────────────────┐          ┌──────────────────────┐
│   YOUR LICENSE       │          │   CLIENT SITE        │
│   SERVER (this)      │          │   (customer)         │
│                      │          │                      │
│  Private Key ──┐     │          │     Public Key       │
│                │     │          │        │              │
│         ┌──────▼──┐  │  send    │  ┌─────▼─────┐       │
│         │  SIGN   │──│──code──▶ │  │  VERIFY   │       │
│         └─────────┘  │          │  └─────┬─────┘       │
│                      │          │        │              │
│                      │          │   ✅ or ❌            │
└──────────────────────┘          └──────────────────────┘
```

1. **You** generate an RSA-2048 key pair on your server
2. **You** embed the public key in your client plugin's code
3. **You** generate a license for a customer's domain → get an activation code
4. **Customer** pastes the activation code in their plugin settings
5. **Client plugin** verifies the signature using the embedded public key — **no API call needed**

## Requirements

- WordPress 5.9+
- PHP 8.0+
- PHP OpenSSL extension
- Apache (for `.htaccess` key protection) or equivalent Nginx config

## Installation

### From GitHub Release

1. Download the latest release ZIP
2. Go to **WP Admin → Plugins → Add New → Upload Plugin**
3. Upload the ZIP and activate

### From Source

```bash
# Clone the repo
git clone https://github.com/vitinhthanhphat-png/ts-license-manager.git

# Copy to your WordPress plugins directory
cp -r ts-license-manager /path/to/wp-content/plugins/

# Install frontend dependencies & build
cd ts-license-manager/frontend
npm install
npm run build
```

> **Note:** The `admin/dist/` directory contains pre-built frontend assets. You only need to run `npm install && npm run build` if you want to modify the admin UI.

## Quick Start

### 1. Generate Key Pair

Go to **WP Admin → License Manager → Keys** and click **"+ Generate New Key Pair"**.

### 2. Embed Public Key

Copy the public key and add it to your client plugin:

```php
// In your plugin's main file or config
define('MY_PLUGIN_PUBLIC_KEY', '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCg...
-----END PUBLIC KEY-----');
```

### 3. Generate License

Go to **License Manager → Generate License**, enter the customer's domain, select license type, and click Generate. Copy the activation code.

### 4. Customer Activates

The customer pastes the activation code in your plugin's settings page. Done!

## Integration

Add this verification function to your client plugin:

```php
function my_plugin_verify_license(string $activation_code): string {
    $public_key = MY_PLUGIN_PUBLIC_KEY;
    $license_data = json_decode(
        base64_decode($activation_code), true
    );

    if (!$license_data || !isset($license_data['sig'], $license_data['data'])) {
        return 'invalid_format';
    }

    // Step 1: Verify RSA signature
    $signature = base64_decode($license_data['sig']);
    $payload   = json_encode($license_data['data']);
    $valid     = openssl_verify(
        $payload, $signature, $public_key,
        OPENSSL_ALGO_SHA256
    );
    if ($valid !== 1) {
        return 'invalid_signature';
    }

    // Step 2: Verify domain (skip on localhost for dev)
    $data    = $license_data['data'];
    $current = parse_url(home_url(), PHP_URL_HOST);
    $is_local = in_array($current, ['localhost', '127.0.0.1', '::1'])
                || str_ends_with($current, '.local');

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

    // License type: $data['type'] = 'lifetime' | 'yearly' | 'trial'
    return 'active';
}
```

### Usage Example

```php
$status = my_plugin_verify_license(get_option('my_plugin_license_code'));

switch ($status) {
    case 'active':
        // Full feature access
        break;
    case 'license_expired':
        // Show renewal notice
        break;
    case 'domain_mismatch':
        // Wrong domain
        break;
    default:
        // Invalid or no license
        break;
}
```

## License Data Structure

The activation code is a base64-encoded JSON string:

```json
{
  "data": {
    "domain": "example.com",
    "type": "lifetime",
    "customer": "Nguyen Van A",
    "created_at": "2026-01-15 10:00:00",
    "expires_at": null,
    "key_id": 1
  },
  "sig": "base64_encoded_rsa_signature"
}
```

| Field | Description |
|-------|-------------|
| `domain` | The domain this license is locked to |
| `type` | `lifetime` (never expires), `yearly` (365 days), `trial` (30 days) |
| `customer` | Customer name (optional) |
| `created_at` | Timestamp when the license was generated |
| `expires_at` | Expiry date, or `null` for lifetime |
| `key_id` | ID of the RSA key pair used to sign |
| `sig` | RSA-SHA256 signature of the `data` payload |

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | WordPress, PHP 8.0+, OpenSSL |
| **Frontend** | Vue 3, Quasar Framework, Pinia |
| **Build** | Vite |
| **Crypto** | RSA-2048, SHA-256 |

## Project Structure

```
ts-license-manager/
├── ts-license-manager.php      # Main plugin file
├── includes/
│   ├── class-admin-menu.php    # WP admin menu registration
│   ├── class-database.php      # DB schema & migrations
│   ├── class-key-manager.php   # RSA key pair operations
│   ├── class-license-generator.php  # License signing logic
│   └── class-rest-api.php      # REST API endpoints
├── frontend/                   # Vue 3 + Quasar SPA source
│   ├── src/
│   │   ├── pages/              # Dashboard, Keys, Generate, Domains, Audit Log, Guide
│   │   ├── stores/             # Pinia state management
│   │   ├── router/             # Vue Router (hash mode)
│   │   └── styles/             # SCSS with WP admin CSS isolation
│   └── package.json
├── admin/
│   └── dist/                   # Pre-built frontend assets
└── README.md
```

## Security

- **Private keys** are stored in `wp-content/uploads/ts-license-keys/` protected by `.htaccess` (deny all)
- **RSA-2048** — industry-standard asymmetric encryption
- All admin endpoints require `manage_options` capability
- REST API uses WordPress nonce verification
- Keys directory includes `index.php` silence file

> ⚠️ **Important:** Back up your `wp-content/uploads/ts-license-keys/` directory. If you lose the private key, existing licenses cannot be verified after a key rotation.

## FAQ

**Can I use this for multiple plugins?**
Yes. TS License Manager is a universal license server. Use the same key pair to sign licenses for different plugins. Differentiate them via the customer name or notes field.

**Do I need an API for verification?**
No. Verification is 100% offline using RSA public key cryptography.

**Can customers share activation codes?**
Codes are domain-locked. A code for `domain-a.com` won't work on `domain-b.com`.

**What about localhost testing?**
The integration snippet automatically skips domain checks on `localhost`, `127.0.0.1`, and `*.local` domains.

## License

GPL-2.0-or-later — see [LICENSE](LICENSE) for details.

## Author

**TechShareVN** — [techsharevn.com](https://techsharevn.com)
