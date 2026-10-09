# HA Force Relative URLs

> **Version:** 2.0.0  
> **Autor:** Alexander Hacker  
> **Lizenz:** GPL-3.0  
> **Mindestvoraussetzungen:** WordPress 6.0+ | PHP 8.1+  

**HA Force Relative URLs** erzwingt saubere, root-relative URLs (`/wp-content/...`, `/mein-beitrag/`) für Medien, Scripts, Styles, Astra-Theme-Assets und Beitragslinks in Multi-Port- und Multidomain-WordPress-Installationen.

---

## 🎯 Einsatzzweck & Hintergrund

In vielen Unternehmens-, Behörden- und Intranet-Netzwerkarchitekturen wird WordPress über unterschiedliche Hostnamen oder Ports angesteuert:
* **Intern / Standard:** `https://intranet.example.org` (Port 443)
* **Extern / Niederlassungen:** `https://intranet.example.org:8443` (Port 8443)
* **Staging / Testumgebungen:** `https://staging.local`

WordPress und moderne Themes (wie **Astra**) generieren standardmäßig absolute URLs mit fest hinterlegtem Hostnamen und Port. Greift ein Client über einen alternativen Port (z. B. `8443`) zu, blockiert der Browser das Laden von Styles, Skripten und Medien, wenn diese mit Port `443` ausgeliefert werden.

Dieses Plugin wandelt alle internen URLs zur Laufzeit in **root-relative Pfade** um. Dadurch nutzt der Browser des Clients automatisch exakt das Protokoll, den Hostnamen und den Port des aktuellen Seitenaufrufs.

---

## ✨ Features & Architektur (v2.0.0)

* **100 % WordPress-Core Hooks:** Keine synchronen cURL-Aufrufe, keine PHP-Worker-Blockaden und kein clientseitiges DOM-Scraping per JavaScript.
* **Astra Theme & CSS-Kompatibilität:** Filtert Enqueued Scripts/Styles (`style_loader_src`, `script_loader_src`) sowie Astra-spezifische dynamische Inline-Styles (`astra_dynamic_theme_css`).
* **Medien & Responsive Images:** Wandelt Bild-URLs, Zwischengrößen und `srcset`-Attribute atomar um.
* **Beitrags- & Seitenlinks:** Konvertiert Links in Beiträgen (`the_content`), Auszügen (`the_excerpt`), Widgets und Permalinks.
* **Schutz kritischer Endpunkte:**
  * WP REST API (`/wp-json/`) bleibt geschützt (kein Bruch des Gutenberg-Block-Editors oder WP-Crons).
  * RSS- und Atom-Feeds behalten absolute URLs (gemäß RFC-Spezifikation).
  * XML-RPC und Sitemaps werden nicht verändert.
* **Standards-Konformität:**
  * Namespace `ha\ForceRelativeUrls` mit modernem PSR-4 Autoloading unter `src/`.
  * Integrierter **Plugin Update Checker (PUC v5.7)** für automatische Updates direkt aus GitHub-Repositories.
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

## ⚙️ Filter & Konfiguration

### 1. Zusätzliche interne Hostnamen ergänzen
Standardmäßig erkennt das Plugin dynamisch die Hosts aus `home_url()`, `site_url()` und dem aktuellen `$_SERVER['HTTP_HOST']`.

Zusätzliche Domains (z. B. historische Hostnamen aus Altdaten) können entweder per Konstante in der `wp-config.php`:

```php
define( 'HA_FRU_ADDITIONAL_HOSTS', [ 'alt-intranet.local', 'staging.local' ] );
```

oder flexibel per Filter im Child-Theme / Plugin ergänzt werden:

```php
add_filter( 'ha_fru_internal_hosts', function( array $hosts ): array {
    $hosts[] = 'alt-intranet.local';
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
Für automatische Updates über private GitHub-Repositories kann in der `wp-config.php` der Access-Token hinterlegt werden:

```php
define( 'HA_GITHUB_UPDATE_TOKEN', 'github_pat_...' );
```

---

## 📝 Changelog

### 2.0.0 (Refactoring & Security-Härtung)
* **Breaking Refactor:** Vollständige Neuentwicklung gemäß modularer Plugin-Standards.
* **Security:** Beseitigung ungeschützter Endpunkte und unsicherer Dateispeicherung im Plugin-Ordner.
* **Performance:** Entfernen aller synchronen cURL-Downloads und Regex-Blockaden beim Seitenaufruf.
* **Multi-Port & Multidomain:** Volle Unterstützung für Zugriffe über wechselnde Ports (z. B. 443 vs. 8443) und Staging-Domains.
* **Astra-Support:** Unterstützung dynamischer Astra-CSS-Regeln über `astra_dynamic_theme_css`.