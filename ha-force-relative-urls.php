<?php
/**
 * Plugin Name:       HA Force Relative URLs
 * Plugin URI:        https://github.com/mystryx1337/ha-force-relative-urls
 * Description:       Erzwingt root-relative URLs für Assets, Skripte, Styles, Bilder und interne Links zur nahtlosen Unterstützung wechselnder Hostnamen und Ports (z. B. Port 443 intern vs. 8443 extern).
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Alexander Hacker
 * Author URI:        https://7south.digital
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       ha-force-relative-urls
 * Domain Path:       /languages
 * 
 * GitHub Plugin URI: mystryx1337/ha-force-relative-urls
 * Primary Branch:    main
 */

namespace ha\ForceRelativeUrls;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// 1. Konstanten
// -----------------------------------------------------------------------------
define( 'HA_FRU_VERSION', '2.0.0' );
define( 'HA_FRU_PATH', plugin_dir_path( __FILE__ ) );
define( 'HA_FRU_URL', plugin_dir_url( __FILE__ ) );
define( 'HA_FRU_BASENAME', plugin_basename( __FILE__ ) );

// -----------------------------------------------------------------------------
// 2. PSR-4 Autoloader für namespace ha\ForceRelativeUrls\
// -----------------------------------------------------------------------------
spl_autoload_register( function( string $class ): void {
    $prefix = 'ha\\ForceRelativeUrls\\';
    $base_dir = HA_FRU_PATH . 'src/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// -----------------------------------------------------------------------------
// 3. Automatischer GitHub-Updater (Private Repo Support)
// -----------------------------------------------------------------------------
function ha_force_relative_urls_get_updater() {
    static $update_checker = null;
    if ( null !== $update_checker ) {
        return $update_checker;
    }

    $puc_file = HA_FRU_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';
    if ( ! file_exists( $puc_file ) ) {
        return null;
    }

    require_once $puc_file;

    $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/mystryx1337/ha-force-relative-urls/',
        __FILE__,
        'ha-force-relative-urls'
    );

    $update_checker->setBranch( 'main' );

    $token = defined( 'HA_GITHUB_UPDATE_TOKEN' ) ? HA_GITHUB_UPDATE_TOKEN : '';
    if ( empty( $token ) && defined( 'GITHUB_ACCESS_TOKEN' ) ) {
        $token = GITHUB_ACCESS_TOKEN;
    }
    $token = apply_filters( 'ha_github_update_token', $token, 'ha-force-relative-urls' );

    if ( ! empty( $token ) ) {
        $update_checker->setAuthentication( $token );
    }

    return $update_checker;
}
ha_force_relative_urls_get_updater();

// -----------------------------------------------------------------------------
// 4. Aktivierungs- und Deaktivierungshooks
// -----------------------------------------------------------------------------
register_activation_hook( __FILE__, [ Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Plugin::class, 'deactivate' ] );

// -----------------------------------------------------------------------------
// 5. Plugin-Initialisierung
// -----------------------------------------------------------------------------
Plugin::get_instance();