<?php
namespace ha\ForceRelativeUrls;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Wandelt absolute URLs interner Hosts in root-relative Pfade um.
 */
class UrlConverter {

    /**
     * Registriert alle WordPress-Filter.
     */
    public function register_hooks(): void {
        // Feeds, REST-API oder XML-RPC nicht verfälschen
        if ( $this->should_skip_request() ) {
            return;
        }

        // 1. Skripte & Styles (Astra Theme & Plugins)
        add_filter( 'style_loader_src', [ $this, 'make_relative' ], 20 );
        add_filter( 'script_loader_src', [ $this, 'make_relative' ], 20 );

        // 2. Themes & Plugins Pfade
        add_filter( 'template_directory_uri', [ $this, 'make_relative' ], 20 );
        add_filter( 'stylesheet_directory_uri', [ $this, 'make_relative' ], 20 );
        add_filter( 'theme_file_uri', [ $this, 'make_relative' ], 20 );
        add_filter( 'parent_theme_file_uri', [ $this, 'make_relative' ], 20 );
        add_filter( 'plugins_url', [ $this, 'make_relative' ], 20 );
        add_filter( 'content_url', [ $this, 'make_relative' ], 20 );

        // 3. Medien & Attachments
        add_filter( 'wp_get_attachment_url', [ $this, 'make_relative' ], 20 );
        add_filter( 'wp_get_attachment_image_src', [ $this, 'filter_attachment_image_src' ], 20 );
        add_filter( 'wp_calculate_image_srcset', [ $this, 'filter_image_srcset' ], 20 );

        // 4. Links zu Beiträgen, Seiten, Taxonomien
        add_filter( 'post_link', [ $this, 'make_relative' ], 20 );
        add_filter( 'page_link', [ $this, 'make_relative' ], 20 );
        add_filter( 'post_type_link', [ $this, 'make_relative' ], 20 );
        add_filter( 'attachment_link', [ $this, 'make_relative' ], 20 );
        add_filter( 'term_link', [ $this, 'make_relative' ], 20 );
        add_filter( 'category_link', [ $this, 'make_relative' ], 20 );
        add_filter( 'tag_link', [ $this, 'make_relative' ], 20 );
        add_filter( 'author_link', [ $this, 'make_relative' ], 20 );
        add_filter( 'day_link', [ $this, 'make_relative' ], 20 );
        add_filter( 'month_link', [ $this, 'make_relative' ], 20 );
        add_filter( 'year_link', [ $this, 'make_relative' ], 20 );

        // 5. Inhalte & HTML-Ausgabe
        add_filter( 'the_content', [ $this, 'filter_html_content' ], 20 );
        add_filter( 'the_excerpt', [ $this, 'filter_html_content' ], 20 );
        add_filter( 'post_thumbnail_html', [ $this, 'filter_html_content' ], 20 );
        add_filter( 'widget_text_content', [ $this, 'filter_html_content' ], 20 );

        // 6. Astra Theme dynamisches CSS
        add_filter( 'astra_dynamic_theme_css', [ $this, 'filter_css_content' ], 20 );
    }

    /**
     * Wandelt eine absolute URL eines internen Hosts in einen root-relativen Pfad um.
     *
     * @param string $url Die zu konvertierende URL.
     * @return string Relativer Pfad oder Original-URL (falls extern/ausgeschlossen).
     */
    public function make_relative( string $url ): string {
        if ( empty( $url ) ) {
            return $url;
        }

        // Bereits relativ oder spezielle Schemata überspringen
        if (
            ( str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' ) ) ||
            str_starts_with( $url, '#' ) ||
            str_starts_with( $url, 'mailto:' ) ||
            str_starts_with( $url, 'tel:' ) ||
            str_starts_with( $url, 'javascript:' ) ||
            str_starts_with( $url, 'data:' )
        ) {
            return $url;
        }

        $parts = wp_parse_url( $url );
        if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
            return $url;
        }

        $host = strtolower( $parts['host'] );
        $internal_hosts = $this->get_internal_hosts();

        if ( ! in_array( $host, $internal_hosts, true ) ) {
            return $url; // Externe URL unberührt lassen
        }

        // Pfad zusammensetzen
        $path = $parts['path'] ?? '/';
        if ( ! str_starts_with( $path, '/' ) ) {
            $path = '/' . $path;
        }

        if ( ! empty( $parts['query'] ) ) {
            $path .= '?' . $parts['query'];
        }

        if ( ! empty( $parts['fragment'] ) ) {
            $path .= '#' . $parts['fragment'];
        }

        return $path;
    }

    /**
     * Filtert Bild-Array von wp_get_attachment_image_src.
     *
     * @param array|false $image Array [url, width, height, is_intermediate]
     * @return array|false
     */
    public function filter_attachment_image_src( $image ) {
        if ( is_array( $image ) && isset( $image[0] ) && is_string( $image[0] ) ) {
            $image[0] = $this->make_relative( $image[0] );
        }
        return $image;
    }

    /**
     * Filtert Responsive Image srcset Quellen.
     *
     * @param array $sources Array von srcset-Definitionen
     * @return array
     */
    public function filter_image_srcset( array $sources ): array {
        foreach ( $sources as &$source ) {
            if ( isset( $source['url'] ) && is_string( $source['url'] ) ) {
                $source['url'] = $this->make_relative( $source['url'] );
            }
        }
        return $sources;
    }

    /**
     * Filtert HTML-Inhalte (z.B. the_content) nach Links, Bildern und Medien.
     *
     * @param string $content
     * @return string
     */
    public function filter_html_content( string $content ): string {
        if ( '' === trim( $content ) || $this->should_skip_request() ) {
            return $content;
        }

        // 1. URLs in Attributen: href, src, action, data-src, data-href
        $attr_pattern = '/\b(href|src|action|data-src|data-href)=([\'"])(https?:\/\/[^\'"\s]+|\/\/[^\'"\s]+)\2/i';
        $content = preg_replace_callback( $attr_pattern, function( $matches ) {
            $attr  = $matches[1];
            $quote = $matches[2];
            $url   = $matches[3];
            return $attr . '=' . $quote . $this->make_relative( $url ) . $quote;
        }, $content );

        // 2. URLs in srcset Attributen
        $srcset_pattern = '/\bsrcset=([\'"])(.*?)\1/is';
        $content = preg_replace_callback( $srcset_pattern, function( $matches ) {
            $quote = $matches[1];
            $val   = $matches[2];
            $parts = explode( ',', $val );
            $new_parts = [];

            foreach ( $parts as $part ) {
                $part = trim( $part );
                if ( preg_match( '/^(https?:\/\/[^\s]+|\/\/[^\s]+)(.*)$/i', $part, $pmatches ) ) {
                    $new_parts[] = $this->make_relative( $pmatches[1] ) . $pmatches[2];
                } else {
                    $new_parts[] = $part;
                }
            }

            return 'srcset=' . $quote . implode( ', ', $new_parts ) . $quote;
        }, $content );

        // 3. URLs in inline style url(...)
        return $this->filter_css_content( $content );
    }

    /**
     * Filtert CSS-Strings und ersetzt url(...) Pfade interner Hosts.
     *
     * @param string $css
     * @return string
     */
    public function filter_css_content( string $css ): string {
        if ( '' === trim( $css ) ) {
            return $css;
        }

        $css_url_pattern = '/url\(\s*([\'"]?)(https?:\/\/[^)\'"\s]+|\/\/[^)\'"\s]+)\1\s*\)/i';
        return preg_replace_callback( $css_url_pattern, function( $matches ) {
            $quote = $matches[1];
            $url   = $matches[2];
            return 'url(' . $quote . $this->make_relative( $url ) . $quote . ')';
        }, $css );
    }

    /**
     * Ermittelt die Liste aller internen Hostnamen (ohne Port).
     *
     * @return array
     */
    public function get_internal_hosts(): array {
        static $cached_hosts = null;
        if ( null !== $cached_hosts ) {
            return $cached_hosts;
        }

        $hosts = [];

        // 1. Host aus home_url() und site_url()
        if ( function_exists( 'home_url' ) ) {
            $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
            if ( ! empty( $home_host ) ) {
                $hosts[] = strtolower( (string) $home_host );
            }
        }
        if ( function_exists( 'site_url' ) ) {
            $site_host = wp_parse_url( site_url(), PHP_URL_HOST );
            if ( ! empty( $site_host ) ) {
                $hosts[] = strtolower( (string) $site_host );
            }
        }

        // 2. Aktueller HTTP_HOST Request-Header (falls Port enthalten ist, Port abtrennen)
        if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
            $req_host = strtolower( (string) $_SERVER['HTTP_HOST'] );
            if ( str_contains( $req_host, ':' ) ) {
                $req_host = explode( ':', $req_host )[0];
            }
            $hosts[] = $req_host;
        }

        // 3. Standardmäßige Intranet-Hostnamen des Landkreises
        $hosts[] = 'intranet.hef-rof.de';
        $hosts[] = 's-app75.lkhefrof.local';

        $hosts = array_values( array_unique( array_filter( $hosts ) ) );

        /**
         * Ermöglicht das Filtern oder Hinzufügen weiterer interner Hostnamen.
         *
         * @param array $hosts Liste der Hostnamen.
         */
        $cached_hosts = apply_filters( 'ha_fru_internal_hosts', $hosts );

        return is_array( $cached_hosts ) ? $cached_hosts : $hosts;
    }

    /**
     * Prüft, ob der aktuelle Request übersprungen werden soll (Feeds, REST, XML-RPC, Sitemaps).
     *
     * @return bool
     */
    public function should_skip_request(): bool {
        // Feeds müssen laut RFC absolute URLs enthalten
        if ( function_exists( 'is_feed' ) && is_feed() ) {
            return true;
        }

        // Robots.txt
        if ( function_exists( 'is_robots' ) && is_robots() ) {
            return true;
        }

        // XML-RPC
        if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
            return true;
        }

        // REST API Anfragen (für Block-Editor, Cron & externe Endpunkte)
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }

        // Sitemaps
        if ( ! empty( $_SERVER['REQUEST_URI'] ) && str_contains( (string) $_SERVER['REQUEST_URI'], 'wp-sitemap' ) ) {
            return true;
        }

        /**
         * Filtert, ob der Request übersprungen werden soll.
         *
         * @param bool $skip Standardmäßig false.
         */
        return (bool) apply_filters( 'ha_fru_skip_request', false );
    }
}