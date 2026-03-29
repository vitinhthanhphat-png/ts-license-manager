<?php
/**
 * TSLM GitHub Updater
 *
 * Lightweight OTA updater — checks GitHub releases for newer versions
 * and injects update data into WordPress's plugin update system.
 *
 * @package TS_License_Manager
 */

namespace TSLM;

defined( 'ABSPATH' ) || exit;

class GitHub_Updater {

	/** @var string GitHub API endpoint */
	private string $api_url;

	/** @var string Plugin basename (e.g. ts-license-manager/ts-license-manager.php) */
	private string $plugin_basename;

	/** @var string Plugin slug */
	private string $slug;

	/** @var string Current plugin version */
	private string $version;

	/** @var string Transient cache key */
	private string $cache_key = 'tslm_github_update';

	/** @var int Cache duration (12 hours) */
	private int $cache_ttl = 43200;

	/**
	 * Constructor.
	 *
	 * @param string $github_user GitHub username/org.
	 * @param string $github_repo GitHub repository name.
	 * @param string $plugin_file Main plugin file (__FILE__ from ts-license-manager.php).
	 */
	public function __construct( string $github_user, string $github_repo, string $plugin_file ) {
		$this->api_url         = "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest";
		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->slug            = dirname( $this->plugin_basename );
		$this->version         = TSLM_VERSION;

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_post_install', [ $this, 'after_install' ], 10, 3 );
	}

	/**
	 * Fetch release data from GitHub (cached).
	 *
	 * @return object|false Release data or false on failure.
	 */
	private function fetch_release() {
		// Clear cache if user clicks "Check Again" in Dashboard > Updates
		if ( isset( $_GET['force-check'] ) && '1' === $_GET['force-check'] ) {
			delete_transient( $this->cache_key );
		}

		$cached = get_transient( $this->cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get( $this->api_url, [
			'timeout' => 10,
			'headers' => [
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
			],
		] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Cache failure for 1 hour to avoid hammering API
			set_transient( $this->cache_key, 'error', 3600 );
			return false;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $release->tag_name ) ) {
			return false;
		}

		set_transient( $this->cache_key, $release, $this->cache_ttl );
		return $release;
	}

	/**
	 * Clean version tag (remove leading "v" if present).
	 *
	 * @param string $tag Version tag from GitHub.
	 * @return string Cleaned version string.
	 */
	private function clean_version( string $tag ): string {
		return ltrim( $tag, 'vV' );
	}

	/**
	 * Get the download URL from a release.
	 *
	 * Prefers an attached .zip asset (has correct folder name inside),
	 * falls back to GitHub's zipball_url (extracts as repo-branch/).
	 *
	 * @param object $release GitHub release object.
	 * @return string Download URL.
	 */
	private function get_download_url( object $release ): string {
		// Look for a .zip asset attached to the release
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if (
					isset( $asset->browser_download_url ) &&
					str_ends_with( $asset->name ?? '', '.zip' )
				) {
					return $asset->browser_download_url;
				}
			}
		}

		// Fallback to zipball (GitHub's auto-generated source ZIP)
		return $release->zipball_url ?? '';
	}

	/**
	 * Filter: inject update data when a newer version exists on GitHub.
	 *
	 * @param object $transient WordPress update_plugins transient.
	 * @return object Modified transient.
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->fetch_release();
		if ( ! $release || 'error' === $release ) {
			return $transient;
		}

		$remote_version = $this->clean_version( $release->tag_name );

		if ( version_compare( $remote_version, $this->version, '>' ) ) {
			$update               = new \stdClass();
			$update->slug         = $this->slug;
			$update->plugin       = $this->plugin_basename;
			$update->new_version  = $remote_version;
			$update->url          = $release->html_url ?? '';
			$update->package      = $this->get_download_url( $release );
			$update->icons        = [];
			$update->banners      = [];
			$update->tested       = '';
			$update->requires     = '5.9';
			$update->requires_php = '8.0';

			$transient->response[ $this->plugin_basename ] = $update;
		} else {
			// Report as up-to-date
			$item               = new \stdClass();
			$item->slug         = $this->slug;
			$item->plugin       = $this->plugin_basename;
			$item->new_version  = $this->version;
			$item->url          = '';
			$item->package      = '';

			$transient->no_update[ $this->plugin_basename ] = $item;
			unset( $transient->response[ $this->plugin_basename ] );
		}

		return $transient;
	}

	/**
	 * Filter: provide plugin info for the "View Details" popup.
	 *
	 * @param false|object|array $result Default result.
	 * @param string             $action API action.
	 * @param object             $args   API arguments.
	 * @return false|object Plugin info object or passthrough.
	 */
	public function plugin_info( $result, string $action, object $args ) {
		if ( 'plugin_information' !== $action || ( $args->slug ?? '' ) !== $this->slug ) {
			return $result;
		}

		$release = $this->fetch_release();
		if ( ! $release || 'error' === $release ) {
			return $result;
		}

		$remote_version = $this->clean_version( $release->tag_name );

		$info                 = new \stdClass();
		$info->name           = 'TS License Manager';
		$info->slug           = $this->slug;
		$info->version        = $remote_version;
		$info->author         = '<a href="https://techsharevn.com">TechShareVN</a>';
		$info->homepage       = 'https://github.com/vitinhthanhphat-png/ts-license-manager';
		$info->requires       = '5.9';
		$info->requires_php   = '8.0';
		$info->tested         = '';
		$info->downloaded     = 0;
		$info->last_updated   = $release->published_at ?? '';
		$info->download_link  = $this->get_download_url( $release );

		$info->sections = [
			'description' => 'Hệ thống tạo và quản lý License Key bằng RSA-2048 Asymmetric Encryption cho WordPress.',
			'changelog'   => nl2br( esc_html( $release->body ?? 'Không có changelog.' ) ),
		];

		return $info;
	}

	/**
	 * Filter: fix directory name after GitHub ZIP extraction.
	 *
	 * GitHub ZIPs extract as "repo-branch/" but WordPress expects the original folder name.
	 *
	 * @param bool|array  $response   Install response.
	 * @param array $hook_extra Extra hook data.
	 * @param array $result     Installation result.
	 * @return array|\WP_Error Modified result.
	 */
	public function after_install( $response, array $hook_extra, array $result ) {
		global $wp_filesystem;

		// Only handle our own plugin updates
		$is_our_plugin = false;
		if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin_basename ) {
			$is_our_plugin = true;
		}
		if ( ! $is_our_plugin ) {
			return $result;
		}

		$proper_destination = trailingslashit( WP_PLUGIN_DIR ) . $this->slug;
		$source             = isset( $result['destination'] ) ? untrailingslashit( $result['destination'] ) : '';

		// Only rename if the extracted directory is different from expected
		if ( $source && $source !== $proper_destination && $wp_filesystem->exists( $source ) ) {
			// Remove old destination if it exists (WordPress might have already cleared it)
			if ( $wp_filesystem->exists( $proper_destination ) ) {
				$wp_filesystem->delete( $proper_destination, true );
			}

			$wp_filesystem->move( $source, $proper_destination );
			$result['destination']      = $proper_destination;
			$result['destination_name'] = $this->slug;
		}

		// Clear update cache so it re-checks fresh
		delete_transient( $this->cache_key );

		// Re-activate plugin
		$active_plugins = get_option( 'active_plugins', [] );
		if ( in_array( $this->plugin_basename, $active_plugins, true ) ) {
			activate_plugin( $this->plugin_basename );
		}

		return $result;
	}
}
