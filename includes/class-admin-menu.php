<?php
namespace TSLM;

defined('ABSPATH') || exit;

class Admin_Menu {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    /**
     * Register admin menu pages
     */
    public function register_menu(): void {
        $page_hook = add_menu_page(
            __('License Manager', 'ts-license-manager'),
            __('License Manager', 'ts-license-manager'),
            'manage_options',
            'ts-license-manager',
            [$this, 'render_spa'],
            'dashicons-admin-network',
            58
        );

        add_action("admin_enqueue_scripts", function (string $hook) use ($page_hook) {
            if ($hook !== $page_hook) {
                return;
            }
            $this->enqueue_assets();
        });
    }

    /**
     * Render the SPA mount point
     */
    public function render_spa(): void {
        echo '<div id="tslm-app" class="wrap"></div>';
    }

    /**
     * Enqueue Quasar SPA assets
     */
    private function enqueue_assets(): void {
        $dist_dir = TSLM_PLUGIN_DIR . 'admin/dist/';
        $dist_url = TSLM_PLUGIN_URL . 'admin/dist/';

        // Find built assets
        $manifest_path = $dist_dir . '.vite/manifest.json';

        if (file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
            $this->enqueue_from_manifest($manifest, $dist_url);
        } else {
            // Fallback: look for any JS/CSS in dist
            $this->enqueue_fallback($dist_dir, $dist_url);
        }

        // Pass data to JS
        wp_localize_script('tslm-app', 'tslmConfig', [
            'restUrl'  => esc_url_raw(rest_url('tslm/v1/')),
            'nonce'    => wp_create_nonce('wp_rest'),
            'adminUrl' => admin_url(),
            'version'  => TSLM_VERSION,
        ]);

        // Remove admin footer clutter for clean SPA
        add_filter('admin_footer_text', '__return_empty_string');
        add_filter('update_footer', '__return_empty_string', 99);
    }

    /**
     * Enqueue from Vite manifest.json
     */
    private function enqueue_from_manifest(array $manifest, string $dist_url): void {
        foreach ($manifest as $key => $entry) {
            // Find entry point (Vite uses 'index.html' as key for HTML entry points)
            if (empty($entry['isEntry'])) {
                continue;
            }

            // Main entry JS
            wp_enqueue_script(
                'tslm-app',
                $dist_url . $entry['file'],
                [],
                TSLM_VERSION,
                ['in_footer' => true, 'strategy' => 'defer']
            );

            // Add type="module" attribute for ES modules
            add_filter('script_loader_tag', function ($tag, $handle) {
                if ($handle === 'tslm-app') {
                    $tag = str_replace(' src=', ' type="module" src=', $tag);
                }
                return $tag;
            }, 10, 2);

            // CSS files associated with this entry
            if (!empty($entry['css'])) {
                foreach ($entry['css'] as $i => $css) {
                    wp_enqueue_style(
                        'tslm-app-css-' . $i,
                        $dist_url . $css,
                        [],
                        TSLM_VERSION
                    );
                }
            }
        }
    }

    /**
     * Fallback enqueue for when manifest is not available
     */
    private function enqueue_fallback(string $dist_dir, string $dist_url): void {
        // Find JS files
        $js_files = glob($dist_dir . 'assets/*.js');
        if (!empty($js_files)) {
            // Enqueue the largest JS file as main entry
            usort($js_files, fn($a, $b) => filesize($b) - filesize($a));
            wp_enqueue_script(
                'tslm-app',
                $dist_url . 'assets/' . basename($js_files[0]),
                [],
                TSLM_VERSION,
                true
            );
        }

        // Find CSS files
        $css_files = glob($dist_dir . 'assets/*.css');
        if (!empty($css_files)) {
            foreach ($css_files as $i => $css) {
                wp_enqueue_style(
                    'tslm-app-css-' . $i,
                    $dist_url . 'assets/' . basename($css),
                    [],
                    TSLM_VERSION
                );
            }
        }
    }
}
