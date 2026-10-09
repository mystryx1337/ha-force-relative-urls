<?php
namespace ha\ForceRelativeUrls;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Kern-Initialisierungsklasse für HA Force Relative URLs.
 */
class Plugin {

    private static ?Plugin $instance = null;
    private UrlConverter $converter;

    /**
     * Singleton-Instanz abrufen.
     */
    public static function get_instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konstruktor: Initialisiert den URL-Konverter und Hooks.
     */
    private function __construct() {
        $this->converter = new UrlConverter();
        add_action( 'init', [ $this, 'register_hooks' ] );
    }

    /**
     * Registriert alle Konverter-Hooks.
     */
    public function register_hooks(): void {
        $this->converter->register_hooks();
    }

    /**
     * Zugriff auf die UrlConverter-Instanz.
     */
    public function get_converter(): UrlConverter {
        return $this->converter;
    }

    /**
     * Plugin-Aktivierung: Bereinigt eventuelle Legacy-Optionen.
     */
    public static function activate(): void {
        // Altreste aus v1.x (z.B. unsichere Proxy-Option) entfernen
        delete_option( 'ha-ajax-proxy' );
    }

    /**
     * Plugin-Deaktivierung.
     */
    public static function deactivate(): void {
        // Keine Hintergrundprozesse oder persistenten Crons vorhanden
    }
}