<?php
namespace TSLM;

defined('ABSPATH') || exit;

class Database {

    public const KEYS_TABLE    = 'tslm_keys';
    public const LICENSE_TABLE = 'tslm_licenses';
    public const AUDIT_TABLE   = 'tslm_audit_log';

    /**
     * Install database tables
     */
    public static function install(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $keys_table    = $wpdb->prefix . self::KEYS_TABLE;
        $license_table = $wpdb->prefix . self::LICENSE_TABLE;
        $audit_table   = $wpdb->prefix . self::AUDIT_TABLE;

        $sql = "
            CREATE TABLE {$keys_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                key_name VARCHAR(100) NOT NULL DEFAULT 'default',
                public_key TEXT NOT NULL,
                private_key_path VARCHAR(500) DEFAULT NULL,
                private_key_hash VARCHAR(64) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                rotated_at DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_active (is_active)
            ) {$charset_collate};

            CREATE TABLE {$license_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                key_id BIGINT UNSIGNED NOT NULL,
                domain VARCHAR(255) NOT NULL,
                license_type VARCHAR(20) NOT NULL DEFAULT 'lifetime',
                customer_name VARCHAR(255) DEFAULT NULL,
                features TEXT DEFAULT NULL,
                max_users INT UNSIGNED NOT NULL DEFAULT 0,
                activation_code LONGTEXT DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_by BIGINT UNSIGNED DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME DEFAULT NULL,
                revoked_at DATETIME DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_domain (domain),
                KEY idx_status (status),
                KEY idx_key_id (key_id),
                KEY idx_type (license_type)
            ) {$charset_collate};

            CREATE TABLE {$audit_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                action VARCHAR(50) NOT NULL,
                entity_type VARCHAR(30) DEFAULT NULL,
                entity_id BIGINT UNSIGNED DEFAULT NULL,
                details LONGTEXT DEFAULT NULL,
                user_id BIGINT UNSIGNED DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_action (action),
                KEY idx_entity (entity_type, entity_id),
                KEY idx_created (created_at)
            ) {$charset_collate};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('tslm_db_version', TSLM_VERSION);
    }

    /**
     * Get table name with prefix
     */
    public static function table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . $name;
    }

    /**
     * Log an audit event
     */
    public static function audit_log(string $action, ?string $entity_type = null, ?int $entity_id = null, array $details = []): void {
        global $wpdb;

        $wpdb->insert(
            self::table(self::AUDIT_TABLE),
            [
                'action'      => $action,
                'entity_type' => $entity_type,
                'entity_id'   => $entity_id,
                'details'     => !empty($details) ? wp_json_encode($details) : null,
                'user_id'     => get_current_user_id(),
                'ip_address'  => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
                'created_at'  => current_time('mysql'),
            ],
            ['%s', '%s', '%d', '%s', '%d', '%s', '%s']
        );
    }
}
