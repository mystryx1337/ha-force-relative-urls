<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package HA_Force_Relative_Urls
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// 1. SICHERHEITSPRÜFUNG: Datenlöschung standardmäßig VERBOTEN
// -----------------------------------------------------------------------------
$delete_data = (bool) get_option( 'ha_force_relative_urls_delete_data_on_uninstall', false );

// Altreste aus v1.x (z.B. unsichere Proxy-Option) bereinigen
delete_option( 'ha-ajax-proxy' );

if ( ! $delete_data ) {
    return;
}

// -----------------------------------------------------------------------------
// 2. DATENBEREINIGUNG (Nur wenn explizit vom Admin aktiviert)
// -----------------------------------------------------------------------------
delete_option( 'ha_force_relative_urls_delete_data_on_uninstall' );
delete_option( 'ha_fru_settings' );