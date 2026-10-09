# HA Force Relative URLs

> **Version:** 2.0.0  
> **Autor:** Alexander Hacker  
> **Lizenz:** GPL-3.0  
> **Mindestvoraussetzungen:** WordPress 6.0+ | PHP 8.1+  

**HA Force Relative URLs** erzwingt saubere, root-relative URLs (`/wp-content/...`, `/mein-beitrag/`) für Medien, Scripts, Styles, Astra-Theme-Assets und Beitragslinks im WordPress-Intranet.

---

## 🎯 Einsatzzweck & Hintergrund

In hybriden Netzwerkarchitekturen (z. B. Kommunal- und Behördenintranets) wird WordPress häufig über unterschiedliche URLs oder Ports angesteuert:
* **Intern:** `https://intranet.hef-rof.de` (Port 443)
* **Extern / Niederlassungen:** `https://intranet.hef-rof.de:8443` (Port 8443)
* **Staging / Migration:** `https://s-app75.lkhefrof.local`

WordPress und moderne Themes (wie **Astra**) speichern oder generieren standardmäßig absolute URLs mit festem Hostnamen und Port. Greift ein Benutzer über Port `8443` zu, blockiert der Browser das Laden von Styles, Skripten und Bildern, wenn diese mit Port `443` hartcodiert sind.

Dieses Plugin wandelt alle internen URLs zur Laufzeit in **root-relative Pfade** um. Dadurch nutzt der Browser des Clients automatisch exakt das Protokoll, den Hostnamen und den Port des aktuellen Seitenaufrufs.

---

## ✨ Features & Architektur (v2.0.0)

* **100 % WordPress-Core Hooks:** Keine synchronen cURL-Aufrufe, keine PHP-Worker-Blockaden und kein clientseitiges DOM-Scraping per JavaScript.
* **Astra Theme & CSS-Kompatibilität:** Filtert sowohl Enqueued Scripts/Styles (`style_loader_src`, `script_loader_src`) als auch Astra-spezifische dynamische Inline-Styles (`astra_dynamic_theme_css`).
* **Medien & Responsive Images:** Wandelt Bild-URLs, Zwischengrößen und `srcset`-Attribute atomar um.
* **Beitrags- & Seitenlinks:** Konvertiert Links in Beiträgen (`the_content`), Auszügen (`the_excerpt`), Menüs und Permalinks.
* **Schutz kritischer Endpunkte:**
  * WP REST API (`/wp-json/`) bleibt geschützt (kein Bruch des Gutenberg-Block-Editors oder WP-Crons).
  * RSS- und Atom-Feeds behalten absolute URLs (gemäß RFC-Spezifikation).
  * XML-RPC und Sitemaps werden nicht verändert.
* **HA-Standards-Konformität:**
  * Namespace `ha\ForceRelativeUrls` mit modernem PSR-4 Autoloading unter `src/`.
  * Integrierter **Plugin Update Checker (PUC v5.7)** für automatische Updates direkt aus privaten GitHub-Repositories.
  * Sichere Deinstallationsroutine `uninstall.php` mit Daten-Schutzwall.

---

## 📂 Struktur

```text
ha-force-relative-urls/
├── ha-force-relative-urls.php   # Haupteinstieg, Header, Autoloader & GitHub-Updater
├── uninstall.php                # Sichere Deinstallation
├── LICENSE.txt                  # GPL-3.0 Lizenz
├── README.md                    # Dokumentation
├── src/
│   ├── Plugin.php               # Initialisierung & Lifecycle
│   └── UrlConverter.php         # Zentrale Konvertierungslogik & WP-Filter
└── vendor/
    └── plugin-update-checker/   # Autarker PUC v5.7 Update-Mechanismus
```

---

## ⚙️ Filter & Anpassungen

### 1. Eigene interne Hostnamen ergänzen
Standardmäßig erkennt das Plugin die Hosts aus `home_url()`, `site_url()`, dem aktuellen `$_SERVER['HTTP_HOST']` sowie `intranet.hef-rof.de` und `s-app75.lkhefrof.local`. Zusätzliche Hostnamen können per Filter ergänzt werden:

```php
add_filter( 'ha_fru_internal_hosts', function( array $hosts ): array {
    $hosts[] = 'mein-weiterer-host.local';
    return $hosts;
} );
```

### 2. Bestimmte Anfragen von der Konvertierung ausschließen
```php
add_filter( 'ha_fru_skip_request', function( bool $skip ): bool {
    if ( isset( $_GET['my_custom_export'] ) ) {
        return true;
    }
    return $skip;
} );
```

### 3. Automatische GitHub-Updates
Für automatische Updates über private GitHub-Repositories wird in der `wp-config.php` der Token hinterlegt:

```php
define( 'HA_GITHUB_UPDATE_TOKEN', 'github_pat_...' );
```

---

## 📝 Changelog

### 2.0.0 (Refactoring & Security-Härtung)
* **Breaking Refactor:** Vollständige Neuentwicklung gemäß `ha-plugin-development-standards`.
* **Security:** Beseitigung der SSRF- und Arbitrary-File-Write-Lücke (Entfernen von `ajax-proxy.php` und `cache/`).
* **Performance:** Entfernen aller synchronen cURL-Downloads und Regex-Blockaden beim Seitenaufruf.
* **Bugfix:** Beseitigung des fehlenden CSS-Stylings im Frontend durch native Filterung von `style_loader_src`.
* **Multi-Port:** Volle Unterstützung für Zugriffe über Port 8443 und Staging-Domains (`s-app75.lkhefrof.local`).
* **Astra-Support:** Unterstützung dynamischer Astra-CSS-Regeln über `astra_dynamic_theme_css`.