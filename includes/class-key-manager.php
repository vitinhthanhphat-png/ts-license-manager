<?php
namespace TSLM;

defined('ABSPATH') || exit;

class Key_Manager {

    /**
     * Get the protected keys directory path
     */
    public static function get_keys_dir(): string {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/ts-license-keys';
    }

    /**
     * Generate a new RSA-2048 key pair
     */
    public function generate_key_pair(string $name = 'default'): array {
        // OpenSSL config for Windows compatibility
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $opensslCnf = $this->find_openssl_config();
        if ($opensslCnf) {
            $config['config'] = $opensslCnf;
        }

        $resource = openssl_pkey_new($config);
        if (!$resource) {
            // Fallback: try with env variable
            if ($opensslCnf) {
                putenv("OPENSSL_CONF={$opensslCnf}");
            }
            $resource = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);
            if (!$resource) {
                throw new \RuntimeException('Failed to generate RSA key pair: ' . openssl_error_string());
            }
        }

        // Export keys
        openssl_pkey_export($resource, $private_key, null, $config);
        $details    = openssl_pkey_get_details($resource);
        $public_key = $details['key'];

        // Save private key to protected directory
        $keys_dir     = self::get_keys_dir();
        $timestamp    = time();
        $safe_name    = sanitize_file_name($name);
        $private_path = "{$keys_dir}/{$safe_name}_{$timestamp}_private.pem";

        if (!is_dir($keys_dir)) {
            wp_mkdir_p($keys_dir);
        }

        file_put_contents($private_path, $private_key);
        chmod($private_path, 0600);

        // Verify the key pair
        $test_msg = '{"test":"verify"}';
        openssl_sign($test_msg, $signature, $private_key, OPENSSL_ALGO_SHA256);
        $verify = openssl_verify($test_msg, $signature, $public_key, OPENSSL_ALGO_SHA256);

        if ($verify !== 1) {
            @unlink($private_path);
            throw new \RuntimeException('Key pair verification failed');
        }

        // All keys remain active. You can choose which key to sign with.

        global $wpdb;

        // Store in database
        $wpdb->insert(
            Database::table(Database::KEYS_TABLE),
            [
                'key_name'         => $name,
                'public_key'       => $public_key,
                'private_key_path' => $private_path,
                'private_key_hash' => hash('sha256', $private_key),
                'created_by'       => get_current_user_id(),
                'created_at'       => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s']
        );

        $key_id = $wpdb->insert_id;

        Database::audit_log('key_generated', 'key', $key_id, [
            'name'      => $name,
            'bits'      => 2048,
            'algorithm' => 'RSA-SHA256',
        ]);

        return [
            'id'          => $key_id,
            'name'        => $name,
            'public_key'  => $public_key,
            'private_key' => $private_key, // Only returned at creation time
            'created_at'  => current_time('mysql'),
            'verified'    => true,
        ];
    }

    /**
     * Get active key pair (or specific key by ID)
     */
    public function get_active_key(?int $key_id = null): ?array {
        global $wpdb;
        $table = Database::table(Database::KEYS_TABLE);

        if ($key_id) {
            $row = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $key_id),
                ARRAY_A
            );
        } else {
            $row = $wpdb->get_row(
                "SELECT * FROM {$table} ORDER BY id DESC LIMIT 1",
                ARRAY_A
            );
        }

        return $row ?: null;
    }

    /**
     * Get all keys
     */
    public function get_all_keys(): array {
        global $wpdb;
        $table = Database::table(Database::KEYS_TABLE);

        return $wpdb->get_results(
            "SELECT id, key_name, public_key, private_key_hash, created_by, created_at
             FROM {$table} ORDER BY created_at DESC",
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get private key content for the active key (or specific key by ID)
     */
    public function get_active_private_key(?int $key_id = null): ?string {
        $key = $this->get_active_key($key_id);
        if (!$key || empty($key['private_key_path'])) {
            return null;
        }

        if (!file_exists($key['private_key_path'])) {
            return null;
        }

        return file_get_contents($key['private_key_path']);
    }

    /**
     * Import a private key for an existing key entry
     */
    public function import_private_key(int $key_id, string $private_key_pem): bool {
        global $wpdb;
        $table = Database::table(Database::KEYS_TABLE);

        $key = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $key_id
        ), ARRAY_A);

        if (!$key) {
            throw new \InvalidArgumentException('Key not found');
        }

        // Verify private key matches public key
        $test_msg = '{"test":"import_verify"}';
        $sign_result = openssl_sign($test_msg, $signature, $private_key_pem, OPENSSL_ALGO_SHA256);
        if (!$sign_result) {
            throw new \RuntimeException('Invalid private key format');
        }

        $verify = openssl_verify($test_msg, $signature, $key['public_key'], OPENSSL_ALGO_SHA256);
        if ($verify !== 1) {
            throw new \RuntimeException('Private key does not match the public key');
        }

        // Save private key
        $keys_dir = self::get_keys_dir();
        $safe_name = sanitize_file_name($key['key_name']);
        $private_path = "{$keys_dir}/{$safe_name}_{$key['id']}_imported.pem";

        file_put_contents($private_path, $private_key_pem);
        chmod($private_path, 0600);

        $wpdb->update(
            $table,
            [
                'private_key_path' => $private_path,
                'private_key_hash' => hash('sha256', $private_key_pem),
            ],
            ['id' => $key_id],
            ['%s', '%s'],
            ['%d']
        );

        Database::audit_log('key_imported', 'key', $key_id, ['name' => $key['key_name']]);

        return true;
    }

    /**
     * Delete a key pair
     */
    public function delete_key(int $key_id): bool {
        global $wpdb;
        $table = Database::table(Database::KEYS_TABLE);

        $key = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $key_id
        ), ARRAY_A);

        if (!$key) {
            return false;
        }

        // Delete private key file
        if (!empty($key['private_key_path']) && file_exists($key['private_key_path'])) {
            @unlink($key['private_key_path']);
        }

        $wpdb->delete($table, ['id' => $key_id], ['%d']);

        Database::audit_log('key_deleted', 'key', $key_id, ['name' => $key['key_name']]);

        return true;
    }

    /**
     * Check if active key has private key available
     */
    public function has_private_key(): bool {
        $key = $this->get_active_key();
        if (!$key || empty($key['private_key_path'])) {
            return false;
        }
        return file_exists($key['private_key_path']);
    }

    /**
     * Find openssl.cnf for Windows environments
     */
    private function find_openssl_config(): ?string {
        $candidates = [
            'C:/Program Files/Common Files/SSL/openssl.cnf',
            'C:/xampp/apache/conf/openssl.cnf',
            'C:/xampp/php/extras/openssl/openssl.cnf',
            'd:/xampp/apache/conf/openssl.cnf',
            'd:/xampp/php/extras/openssl/openssl.cnf',
            '/etc/ssl/openssl.cnf',
            '/usr/local/ssl/openssl.cnf',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
