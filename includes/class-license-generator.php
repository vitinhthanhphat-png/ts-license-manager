<?php
namespace TSLM;

defined('ABSPATH') || exit;

class License_Generator {

    private Key_Manager $key_manager;

    public function __construct() {
        $this->key_manager = new Key_Manager();
    }

    /**
     * Generate a signed license activation code
     */
    public function generate(string $domain, array $options = []): array {
        $domain = strtolower(trim($domain));
        if (!$this->is_valid_domain($domain)) {
            throw new \InvalidArgumentException("Invalid domain: {$domain}");
        }

        $key_id = !empty($options['key_id']) ? (int) $options['key_id'] : null;

        // Get active private key (either specified by key_id, or the latest active one)
        $private_key = $this->key_manager->get_active_private_key($key_id);
        if (!$private_key) {
            throw new \RuntimeException('No signed private key found. Generate or import a key first.');
        }

        $active_key = $this->key_manager->get_active_key($key_id);

        $type = $options['type'] ?? 'lifetime';
        if (!in_array($type, ['lifetime', 'yearly', 'trial'], true)) {
            throw new \InvalidArgumentException("Invalid license type: {$type}");
        }

        $expires_at = $this->calculate_expiry($type, $options['expires'] ?? null);

        // Build payload
        $payload = [
            'v'          => 2,
            'domain'     => $domain,
            'type'       => $type,
            'created_at' => time(),
            'expires_at' => $expires_at,
            'customer'   => $options['customer'] ?? null,
        ];

        // Remove null values
        $payload = array_filter($payload, fn($v) => $v !== null);

        // Sign the payload
        $package = $this->sign_data($payload, $key_id);

        $activation_code = base64_encode(json_encode($package, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // Store in database
        global $wpdb;
        $wpdb->insert(
            Database::table(Database::LICENSE_TABLE),
            [
                'key_id'          => $active_key['id'],
                'domain'          => $domain,
                'license_type'    => $type,
                'customer_name'   => $options['customer'] ?? null,
                'activation_code' => $activation_code,
                'status'          => 'active',
                'created_by'      => get_current_user_id(),
                'created_at'      => current_time('mysql'),
                'expires_at'      => $expires_at ? gmdate('Y-m-d H:i:s', $expires_at) : null,
                'notes'           => $options['notes'] ?? null,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        $license_id = $wpdb->insert_id;

        Database::audit_log('license_created', 'license', $license_id, [
            'domain'   => $domain,
            'type'     => $type,
            'customer' => $options['customer'] ?? null,
        ]);

        return [
            'id'              => $license_id,
            'domain'          => $domain,
            'type'            => $type,
            'customer'        => $options['customer'] ?? null,
            'activation_code' => $activation_code,
            'created_at'      => current_time('mysql'),
            'expires_at'      => $expires_at ? gmdate('Y-m-d H:i:s', $expires_at) : null,
        ];
    }

    /**
     * Verify a license code
     */
    public function verify(string $code, ?string $domain = null, ?int $key_id = null): array {
        $active_key = $this->key_manager->get_active_key($key_id);
        if (!$active_key) {
            return ['valid' => false, 'error' => $key_id ? 'Original public key not found' : 'No active key pair found'];
        }

        $decoded = base64_decode($code, true);
        if ($decoded === false) {
            return ['valid' => false, 'error' => 'Invalid base64 encoding'];
        }

        $package = json_decode($decoded, true);
        if (!$package || !isset($package['sig'], $package['data'])) {
            return ['valid' => false, 'error' => 'Invalid package structure'];
        }

        $signature = base64_decode($package['sig'], true);
        if ($signature === false) {
            return ['valid' => false, 'error' => 'Invalid signature encoding'];
        }

        $payload_json = json_encode($package['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $result = openssl_verify($payload_json, $signature, $active_key['public_key'], OPENSSL_ALGO_SHA256);

        if ($result !== 1) {
            return ['valid' => false, 'error' => 'Signature verification failed'];
        }

        $data = $package['data'];

        // Check domain
        if ($domain !== null && strtolower(trim($domain)) !== ($data['domain'] ?? '')) {
            return ['valid' => false, 'error' => 'Domain mismatch', 'data' => $data];
        }

        // Check expiry
        if (isset($data['expires_at']) && $data['expires_at'] > 0 && $data['expires_at'] < time()) {
            return ['valid' => false, 'error' => 'License expired', 'data' => $data];
        }

        return ['valid' => true, 'data' => $data];
    }

    /**
     * Get all licenses with optional filters
     */
    public function get_licenses(array $filters = []): array {
        global $wpdb;
        $table = Database::table(Database::LICENSE_TABLE);

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = $filters['status'];
        }

        if (!empty($filters['domain'])) {
            $where[] = 'domain LIKE %s';
            $params[] = '%' . $wpdb->esc_like($filters['domain']) . '%';
        }

        if (!empty($filters['type'])) {
            $where[] = 'license_type = %s';
            $params[] = $filters['type'];
        }

        $where_sql = implode(' AND ', $where);
        $order = 'ORDER BY created_at DESC';

        $limit = '';
        if (!empty($filters['per_page'])) {
            $page = max(1, (int) ($filters['page'] ?? 1));
            $per_page = (int) $filters['per_page'];
            $offset = ($page - 1) * $per_page;
            $limit = $wpdb->prepare(' LIMIT %d OFFSET %d', $per_page, $offset);
        }

        $sql = "SELECT * FROM {$table} WHERE {$where_sql} {$order}{$limit}";
        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
            $total = $wpdb->get_var($wpdb->prepare($count_sql, ...$params));
        } else {
            $results = $wpdb->get_results($sql, ARRAY_A);
            $total = $wpdb->get_var($count_sql);
        }

        return [
            'items' => $results ?: [],
            'total' => (int) $total,
        ];
    }

    /**
     * Delete a license
     */
    public function delete_license(int $license_id): bool {
        global $wpdb;
        $table = Database::table(Database::LICENSE_TABLE);

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $license_id
        ), ARRAY_A);

        if (!$license) {
            return false;
        }

        $wpdb->delete(
            $table,
            ['id' => $license_id],
            ['%d']
        );

        Database::audit_log('license_deleted', 'license', $license_id, [
            'domain' => $license['domain'],
        ]);

        return true;
    }

    /**
     * Get dashboard statistics
     */
    public function get_stats(): array {
        global $wpdb;
        $table = Database::table(Database::LICENSE_TABLE);
        $keys_table = Database::table(Database::KEYS_TABLE);

        $total     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $active    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");
        $expired   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'expired'");
        $trial     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE license_type = 'trial' AND status = 'active'");
        $lifetime  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE license_type = 'lifetime' AND status = 'active'");
        $yearly    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE license_type = 'yearly' AND status = 'active'");
        $has_keys  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$keys_table}") > 0;

        // Unique domains
        $domains = (int) $wpdb->get_var("SELECT COUNT(DISTINCT domain) FROM {$table} WHERE status = 'active'");

        // Recent licenses (last 30 days)
        $recent = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
            gmdate('Y-m-d H:i:s', strtotime('-30 days'))
        ));

        return [
            'total'          => $total,
            'active'         => $active,
            'expired'        => $expired,
            'trial'          => $trial,
            'lifetime'       => $lifetime,
            'yearly'         => $yearly,
            'unique_domains' => $domains,
            'recent_30d'     => $recent,
            'has_active_key' => $has_keys,
        ];
    }

    /**
     * Calculate expiry timestamp
     */
    private function calculate_expiry(string $type, ?string $custom_expires): ?int {
        if ($custom_expires) {
            $ts = strtotime($custom_expires);
            if ($ts === false) {
                throw new \InvalidArgumentException("Invalid date: {$custom_expires}");
            }
            return $ts;
        }

        return match ($type) {
            'lifetime' => null,
            'yearly'   => time() + 365 * 86400,
            'trial'    => time() + 30 * 86400,
        };
    }


    /**
     * Validate a domain name
     */
    private function is_valid_domain(string $domain): bool {
        if (in_array($domain, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }
        $domain_only = explode(':', $domain)[0];
        if (in_array($domain_only, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }
        return filter_var($domain_only, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    /**
     * Sign arbitrary data array with the active RSA private key.
     * Required for heartbeat 'call home' signed responses.
     */
    public function sign_data(array $data, ?int $key_id = null): array {
        $private_key = $this->key_manager->get_active_private_key($key_id);
        if (!$private_key) {
            throw new \RuntimeException('No signed private key found. Generate or import a key first.');
        }

        $payload_json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pkey_resource = openssl_pkey_get_private($private_key);
        
        if (!$pkey_resource) {
            throw new \RuntimeException('Failed to load private key: ' . openssl_error_string());
        }

        $sign_result = openssl_sign($payload_json, $signature, $pkey_resource, OPENSSL_ALGO_SHA256);
        if (!$sign_result) {
            throw new \RuntimeException('Failed to sign payload: ' . openssl_error_string());
        }

        return [
            'sig'  => base64_encode($signature),
            'data' => $data,
        ];
    }
}
