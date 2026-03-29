<?php
namespace TSLM;

defined('ABSPATH') || exit;

class Rest_Api {

    private const NAMESPACE = 'tslm/v1';

    /**
     * Register all REST routes
     */
    public function register_routes(): void {
        // ── Dashboard ──
        register_rest_route(self::NAMESPACE, '/dashboard/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_dashboard_stats'],
            'permission_callback' => [$this, 'check_admin'],
        ]);

        // ── Keys ──
        register_rest_route(self::NAMESPACE, '/keys', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_keys'],
            'permission_callback' => [$this, 'check_admin'],
        ]);

        register_rest_route(self::NAMESPACE, '/keys/generate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'generate_key'],
            'permission_callback' => [$this, 'check_admin'],
            'args'                => [
                'name' => [
                    'type'              => 'string',
                    'default'           => 'default',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/keys/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'delete_key'],
            'permission_callback' => [$this, 'check_admin'],
            'args'                => [
                'id' => [
                    'type'              => 'integer',
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/keys/(?P<id>\d+)/import', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_private_key'],
            'permission_callback' => [$this, 'check_admin'],
        ]);

        register_rest_route(self::NAMESPACE, '/keys/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_key_status'],
            'permission_callback' => [$this, 'check_admin'],
        ]);

        // ── Licenses ──
        register_rest_route(self::NAMESPACE, '/licenses', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_licenses'],
            'permission_callback' => [$this, 'check_admin'],
            'args'                => [
                'status'   => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'domain'   => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'type'     => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'page'     => ['type' => 'integer', 'default' => 1],
                'per_page' => ['type' => 'integer', 'default' => 20],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/licenses/generate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'generate_license'],
            'permission_callback' => [$this, 'check_admin'],
            'args'                => [
                'domain' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'type' => [
                    'type'              => 'string',
                    'default'           => 'lifetime',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'customer' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'features' => [
                    'type'    => 'string',
                    'default' => 'all',
                ],
                'max_users' => [
                    'type'    => 'integer',
                    'default' => 0,
                ],
                'expires' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'notes' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/licenses/(?P<id>\d+)/revoke', [
            'methods'             => 'PUT',
            'callback'            => [$this, 'revoke_license'],
            'permission_callback' => [$this, 'check_admin'],
        ]);

        register_rest_route(self::NAMESPACE, '/licenses/(?P<id>\d+)/verify', [
            'methods'             => 'POST',
            'callback'            => [$this, 'verify_license'],
            'permission_callback' => [$this, 'check_admin'],
        ]);

        register_rest_route(self::NAMESPACE, '/licenses/bulk', [
            'methods'             => 'POST',
            'callback'            => [$this, 'bulk_generate'],
            'permission_callback' => [$this, 'check_admin'],
        ]);

        // ── System ──
        register_rest_route(self::NAMESPACE, '/system/backup', [
            'methods'             => 'POST',
            'callback'            => [$this, 'system_backup'],
            'permission_callback' => [$this, 'check_admin'],
        ]);

        register_rest_route(self::NAMESPACE, '/system/restore', [
            'methods'             => 'POST',
            'callback'            => [$this, 'system_restore'],
            'permission_callback' => [$this, 'check_admin'],
        ]);

        // ── Audit Log ──
        register_rest_route(self::NAMESPACE, '/audit-log', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_audit_log'],
            'permission_callback' => [$this, 'check_admin'],
            'args'                => [
                'page'     => ['type' => 'integer', 'default' => 1],
                'per_page' => ['type' => 'integer', 'default' => 50],
                'action'   => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            ],
        ]);
    }

    /**
     * Permission: manage_options required
     */
    public function check_admin(): bool {
        return current_user_can('manage_options');
    }

    // ── Dashboard ──────────────────────────────────

    public function get_dashboard_stats(): \WP_REST_Response {
        $generator = new License_Generator();
        return new \WP_REST_Response($generator->get_stats());
    }

    // ── Keys ───────────────────────────────────────

    public function get_keys(): \WP_REST_Response {
        $km = new Key_Manager();
        $keys = $km->get_all_keys();

        // Sanitize: never expose private key paths
        foreach ($keys as &$key) {
            unset($key['private_key_path']);
            $key['has_private_key'] = !empty($key['private_key_hash']);
        }

        return new \WP_REST_Response($keys);
    }

    public function generate_key(\WP_REST_Request $request): \WP_REST_Response {
        try {
            $km = new Key_Manager();
            $result = $km->generate_key_pair($request->get_param('name') ?: 'default');

            return new \WP_REST_Response([
                'success'     => true,
                'id'          => $result['id'],
                'name'        => $result['name'],
                'public_key'  => $result['public_key'],
                'private_key' => $result['private_key'], // Only at creation time!
                'created_at'  => $result['created_at'],
                'message'     => 'Key pair generated. Download the private key NOW — it cannot be retrieved later.',
            ]);
        } catch (\Throwable $e) {
            return new \WP_REST_Response([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function delete_key(\WP_REST_Request $request): \WP_REST_Response {
        $km = new Key_Manager();
        $deleted = $km->delete_key((int) $request->get_param('id'));

        return new \WP_REST_Response([
            'success' => $deleted,
            'message' => $deleted ? 'Key deleted' : 'Key not found',
        ], $deleted ? 200 : 404);
    }

    public function import_private_key(\WP_REST_Request $request): \WP_REST_Response {
        try {
            $km = new Key_Manager();
            $private_key = $request->get_param('private_key');

            if (empty($private_key)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'error'   => 'Private key content is required',
                ], 400);
            }

            $km->import_private_key((int) $request->get_param('id'), $private_key);

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Private key imported successfully',
            ]);
        } catch (\Throwable $e) {
            return new \WP_REST_Response([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400);
        }
    }

    public function get_key_status(): \WP_REST_Response {
        $km = new Key_Manager();
        $active = $km->get_active_key();

        return new \WP_REST_Response([
            'has_active_key'    => $active !== null,
            'has_private_key'   => $km->has_private_key(),
            'active_key_id'     => $active['id'] ?? null,
            'active_key_name'   => $active['key_name'] ?? null,
            'active_key_date'   => $active['created_at'] ?? null,
        ]);
    }

    // ── Licenses ───────────────────────────────────

    public function get_licenses(\WP_REST_Request $request): \WP_REST_Response {
        $generator = new License_Generator();
        $result = $generator->get_licenses([
            'status'   => $request->get_param('status'),
            'domain'   => $request->get_param('domain'),
            'type'     => $request->get_param('type'),
            'page'     => $request->get_param('page'),
            'per_page' => $request->get_param('per_page'),
        ]);

        return new \WP_REST_Response($result);
    }

    public function generate_license(\WP_REST_Request $request): \WP_REST_Response {
        try {
            $generator = new License_Generator();
            $result = $generator->generate(
                $request->get_param('domain'),
                [
                    'type'      => $request->get_param('type'),
                    'customer'  => $request->get_param('customer'),
                    'features'  => $request->get_param('features'),
                    'max_users' => $request->get_param('max_users'),
                    'expires'   => $request->get_param('expires'),
                    'notes'     => $request->get_param('notes'),
                ]
            );

            return new \WP_REST_Response([
                'success' => true,
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            return new \WP_REST_Response([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400);
        }
    }

    public function revoke_license(\WP_REST_Request $request): \WP_REST_Response {
        $generator = new License_Generator();
        $revoked = $generator->revoke_license((int) $request->get_param('id'));

        return new \WP_REST_Response([
            'success' => $revoked,
            'message' => $revoked ? 'License revoked' : 'License not found',
        ], $revoked ? 200 : 404);
    }

    public function verify_license(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $table = Database::table(Database::LICENSE_TABLE);

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            (int) $request->get_param('id')
        ), ARRAY_A);

        if (!$license || empty($license['activation_code'])) {
            return new \WP_REST_Response(['valid' => false, 'error' => 'License not found'], 404);
        }

        $generator = new License_Generator();
        $result = $generator->verify($license['activation_code'], $license['domain']);

        return new \WP_REST_Response($result);
    }

    public function bulk_generate(\WP_REST_Request $request): \WP_REST_Response {
        $domains = $request->get_param('domains');
        if (!is_array($domains) || empty($domains)) {
            return new \WP_REST_Response([
                'success' => false,
                'error'   => 'Array of domain objects required',
            ], 400);
        }

        $generator = new License_Generator();
        $results = [];
        $errors = [];

        foreach ($domains as $item) {
            try {
                $domain = is_string($item) ? $item : ($item['domain'] ?? '');
                $options = is_array($item) ? $item : [];

                $result = $generator->generate($domain, $options);
                $results[] = $result;
            } catch (\Throwable $e) {
                $errors[] = [
                    'domain' => $domain ?? 'unknown',
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return new \WP_REST_Response([
            'success'  => empty($errors),
            'results'  => $results,
            'errors'   => $errors,
            'total'    => count($results),
            'failed'   => count($errors),
        ]);
    }

    // ── Audit Log ──────────────────────────────────

    public function get_audit_log(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        $table = Database::table(Database::AUDIT_TABLE);

        $page     = max(1, (int) $request->get_param('page'));
        $per_page = min(100, max(1, (int) $request->get_param('per_page')));
        $offset   = ($page - 1) * $per_page;

        $where = ['1=1'];
        $params = [];

        $action_filter = $request->get_param('action');
        if ($action_filter) {
            $where[] = 'action = %s';
            $params[] = $action_filter;
        }

        $where_sql = implode(' AND ', $where);

        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $data_sql  = "SELECT a.*, u.display_name as user_name 
                      FROM {$table} a 
                      LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
                      WHERE {$where_sql} 
                      ORDER BY a.created_at DESC 
                      LIMIT %d OFFSET %d";

        $count_params = $params;
        $params[] = $per_page;
        $params[] = $offset;

        $total = !empty($count_params)
            ? (int) $wpdb->get_var($wpdb->prepare($count_sql, ...$count_params))
            : (int) $wpdb->get_var($count_sql);

        $items = !empty($params)
            ? $wpdb->get_results($wpdb->prepare($data_sql, ...$params), ARRAY_A)
            : $wpdb->get_results($data_sql, ARRAY_A);

        // Decode details JSON
        foreach ($items as &$item) {
            if (!empty($item['details'])) {
                $item['details'] = json_decode($item['details'], true) ?: $item['details'];
            }
        }

        return new \WP_REST_Response([
            'items' => $items ?: [],
            'total' => $total,
            'page'  => $page,
            'pages' => ceil($total / $per_page),
        ]);
    }

    /**
     * Backup system (Database + Keys)
     */
    public function system_backup(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        if (!class_exists('ZipArchive')) {
            return new \WP_Error('no_zip', 'Server does not support ZipArchive', ['status' => 500]);
        }

        $password = $request->get_param('password');
        if (empty($password)) {
            return new \WP_Error('missing_params', 'Password is required', ['status' => 400]);
        }

        $json_data = Database::export_to_json();
        
        $upload_dir = wp_upload_dir();
        $keys_dir   = $upload_dir['basedir'] . '/ts-license-keys';
        
        $temp_zip = sys_get_temp_dir() . '/tslm_backup_' . time() . '.zip';
        $zip = new \ZipArchive();
        
        if ($zip->open($temp_zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return new \WP_Error('zip_fail', 'Cannot create zip file', ['status' => 500]);
        }

        $zip->addFromString('database.json', $json_data);

        if (is_dir($keys_dir)) {
            $files = scandir($keys_dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (str_ends_with($file, '.pem')) {
                    $zip->addFile($keys_dir . '/' . $file, 'keys/' . $file);
                }
            }
        }
        
        $zip->setPassword($password);
        if (defined('\ZipArchive::EM_AES_256')) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $zip->setEncryptionIndex($i, \ZipArchive::EM_AES_256);
            }
        } else {
            // Fallback for older PHP
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $zip->setEncryptionIndex($i, \ZipArchive::EM_TRAD_PKWARE);
            }
        }

        $zip->close();
        
        $base64 = base64_encode(file_get_contents($temp_zip));
        unlink($temp_zip);

        Database::audit_log('system_backup');

        return new \WP_REST_Response([
            'success' => true,
            'file'    => $base64,
            'name'    => 'tslm_backup_' . date('Ymd_His') . '.zip'
        ]);
    }

    /**
     * Restore system (Database + Keys)
     */
    public function system_restore(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        if (!class_exists('ZipArchive')) {
            return new \WP_Error('no_zip', 'Server does not support ZipArchive', ['status' => 500]);
        }

        $password = $request->get_param('password');
        $files    = $request->get_file_params();

        if (empty($password) || empty($files['file'])) {
            return new \WP_Error('missing_params', 'File and password are required', ['status' => 400]);
        }

        $temp_zip = $files['file']['tmp_name'];
        if (!file_exists($temp_zip)) {
            return new \WP_Error('upload_fail', 'File upload failed', ['status' => 400]);
        }

        $zip = new \ZipArchive();
        if ($zip->open($temp_zip) !== true) {
            return new \WP_Error('zip_invalid', 'Invalid zip file format', ['status' => 400]);
        }

        if ($zip->setPassword($password) !== true) {
            $zip->close();
            return new \WP_Error('zip_password', 'Failed to set decryption password', ['status' => 400]);
        }

        // Try reading database.json to verify password
        $json_data = $zip->getFromName('database.json');
        if ($json_data === false) {
            $zip->close();
            return new \WP_Error('zip_decrypt_fail', 'Incorrect password or missing database.json inside archive', ['status' => 400]);
        }

        // Import Database (Truncates active tables!)
        $imported = Database::import_from_json($json_data);
        if (!$imported) {
            $zip->close();
            return new \WP_Error('db_fail', 'Failed to import JSON data to database', ['status' => 500]);
        }

        // Restore keys
        $upload_dir = wp_upload_dir();
        $keys_dir   = $upload_dir['basedir'] . '/ts-license-keys';
        
        if (is_dir($keys_dir)) {
            $existing = scandir($keys_dir);
            foreach ($existing as $file) {
                if (str_ends_with($file, '.pem')) {
                    unlink($keys_dir . '/' . $file);
                }
            }
        } else {
            wp_mkdir_p($keys_dir);
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'keys/') && str_ends_with($name, '.pem')) {
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    file_put_contents($keys_dir . '/' . basename($name), $content);
                }
            }
        }

        $zip->close();
        Database::audit_log('system_restore');

        return new \WP_REST_Response([
            'success' => true,
            'message' => 'System restored successfully'
        ]);
    }
}
