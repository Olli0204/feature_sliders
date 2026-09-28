# Merkmal-Bereichsfilter (JTL-Shop 5.7.0 – 5.8.1)

Legt im Backend **je Merkmal** fest, wie es im Merkmalfilter der Artikelliste dargestellt wird:

| Darstellung | Wawi-Werte | Treffer |
|---|---|---|
| Standard-Filter (Checkboxen) | beliebig | JTL-Standard |
| Schieberegler: **Bereich auf Bereich** | Bereiche, z. B. Körpergewicht „35 - 55 kg“ | je nach Treffer-Logik, Standard: Artikel-Bereich überschneidet sich mit der Auswahl |
| Schieberegler: **Bereich auf Einzelwerte** | einzelne Zahlen, z. B. Länge „154 cm“ | Wert liegt in der Auswahl |
| **Boxen** (Werte als Buttons) | beliebig, z. B. Fahrlevel, Profil | JTL-Standard – nur die Optik ändert sich |

Weitere Darstellungsarten können später in derselben Tabelle ergänzt werden (`CharacteristicConfig::DISPLAYS`).

## Einrichtung

Der Plugin-Ordner im Shop muss `plugins/feature_sliders` heißen – der Repo-Name stimmt überein, ein einfacher Clone reicht (= PluginID, Namespace `Plugin\feature_sliders`):

```bash
git clone git@github.com:Olli0204/feature_sliders.git
```

1. Plugin installieren bzw. aktualisieren.
2. Tab **Merkmale** → „Merkmal hinzufügen“: Merkmal suchen (auch über Werte), Darstellung wählen. Danach öffnet sich
   der Bearbeiten-Dialog mit Live-Vorschau und Werteprüfung: Einheit (leer = aus den Wawi-Werten), Schrittweite,
   Treffer-Logik (nur Bereich auf Bereich), Boxen pro Zeile (automatisch oder fest 1–4) und „Aufgeklappt“.
3. Fertig – Regler und Boxen erscheinen automatisch im Merkmalfilter (Seitenleiste und Filter-Dialog). Voraussetzung
   ist nur der normale JTL-Merkmalfilter (*Einstellungen → Navigationsfilter → Merkmalfilter benutzen*).

Die Übersicht zeigt nur die eingestellten Merkmale mit Aktiv-Schalter, Kurzinfo und Werte-Status. **Aus** = die
Einstellungen bleiben erhalten, im Shop erscheint wieder der normale Checkbox-Filter. **Entfernen** löscht die
Einstellungen des Merkmals.

Beim Update von 1.1.x übernimmt die Migration das bisher eingestellte Merkmal (Körpergewicht) samt Einheit,
Schrittweite, Treffer-Logik und „Aufgeklappt“ als „Bereich auf Bereich“.

Tab **Einstellungen**: „Filter aktiv“ (alle Regler an/aus) und das URL-Parameter-Präfix.

## Boxen-Darstellung

Die Werte erscheinen als gleich breite Kacheln im Raster statt als Checkliste, ohne Trefferanzahl. Links,
Mehrfachauswahl, Aktiv-Status und PRG-Pattern kommen unverändert aus dem JTL-Merkmalfilter; im Filter-Dialog lädt NOVA
wie gewohnt per AJAX nach (Klasse `filter-item`). Die Spaltenzahl ergibt sich je Merkmal aus dem längsten Wert
(`Bootstrap::buttonColumns()`): bis 3 Zeichen 4 Spalten (S/M/L/XL), bis 9 Zeichen 3 (Damen/Herren/Unisex), bis 16
Zeichen 2 (Fortgeschritten, Hybrid-Camber), sonst 1 – oder fest im Bearbeiten-Dialog. Optik: Rahmen/Schrift wie die Eingabefelder des Themes,
halbfett und zentriert, eingerückt wie der Preisregler, ausgewählte Werte in der Akzentfarbe
(`--mrf-accent` → `--primary` → `#FFA54F`).
„Aufgeklappt“ gilt auch hier; Einheit, Schrittweite und Treffer-Logik betreffen nur Schieberegler.

## Funktionsweise

- Pro Regler-Merkmal ein eigener JTL-Produktfilter (`Filter/RangeFilter.php`, registriert über
  `HOOK_PRODUCTFILTER_CREATE`), URL-Parameter `?mrf<Merkmal-ID>=<von>_<bis>`, z. B. `?mrf7=50_60&mrf9=152_156`.
  Die Filter kombinieren sich mit allen anderen, erscheinen in den aktiven Filtern („Körpergewicht: 50 – 60 kg ×“)
  und werden von „Alle Filter entfernen“ mit zurückgesetzt.
- Die Merkmalwerte werden in PHP geparst (`Filter/RangeParser.php`) und in eine `kMerkmalWert IN (…)`-Bedingung
  übersetzt – Wawi-Abgleiche wirken sofort, keine Synchronisation.
- Werte an Variationskindern zählen für den Vaterartikel mit.
- Die Reglerskala ergibt sich aus den Werten der Artikel in der aktuellen Kategorie/Suche – mit allen anderen
  aktiven Filtern (auch anderen Reglern), ohne den eigenen.
- Keine Box-Konfiguration: Der Regler ersetzt im NOVA-Merkmalfilter die Checkbox-Werte des Merkmals
  (Seitenleiste: Block `boxes-box-filter-characteristics-characteristics`, Filter-Dialog:
  `snippets-filter-mobile-filters-collapse`). Titel, Aufklapp-Pfeil, Trennlinie und Position (Merkmal-Sortierung
  der Wawi) bleiben NOVA-Original. Fehlt das Merkmal in der Liste (z. B. 0 Treffer), setzt das Plugin einen
  Platzhalter ein, damit der Regler erreichbar bleibt.
- Optik: exakt das Markup des NOVA-Preisfilters, daher greifen die Theme-Regeln 1:1. Zurückgesetzt wird wie beim
  Preisfilter über die aktiven Filter oder indem man den Regler wieder ganz aufzieht.

### Erkannte Schreibweisen

Bereich auf Bereich:

| Wawi-Wert | Bereich |
|---|---|
| `35 - 55 kg`, `35–55kg`, `35 bis 55 kg`, `35,5 - 55 kg` | 35 – 55 |
| `bis 55 kg`, `< 55 kg`, `max. 55 kg` | nach unten offen – 55 |
| `ab 80 kg`, `80+ kg`, `> 80 kg`, `über 80 kg` | 80 – nach oben offen |
| `55 kg` | 55 – 55 |

Bereich auf Einzelwerte: die erste Zahl zählt – `154 cm`, `154W`, `154,5 cm`, `154 / 157 cm` → 154 bzw. 154,5.

Werte ohne Zahl werden ignoriert („Werte prüfen“ markiert sie). Die Einheit wird automatisch übernommen, wenn die
Mehrheit der Werte dieselbe Einheit hinter der Zahl trägt (`kg`, `cm`, `%`, `Zoll` …).

### Treffer-Logik (Bereich auf Bereich)

- **Überschneiden** (Standard): Artikel `35–55` passt zu Auswahl `50–70`, `20–40`, `40–45`, `55–60`.
- **Enthält**: Artikel-Bereich muss die Auswahl vollständig abdecken.
- **Liegt in**: Artikel-Bereich muss vollständig in der Auswahl liegen.

## Dateien

| Datei | Zweck |
|---|---|
| `Bootstrap.php` | Filter je aktivem Merkmal registrieren, Slider-/Boxen-Daten an Smarty (`$mrfSliders`, `$mrfButtons`), Merkmal im Merkmalfilter sicherstellen/aufklappen |
| `Admin/CharacteristicsAdmin.php` | Tab „Merkmale“: Aktionen speichern/an-aus/entfernen (CSRF), Übersicht, Vorschau- und Prüfdaten |
| `Filter/RangeFilter.php` | JTL-Filter je Merkmal: SQL-Bedingung, Reglerskala, Einheit, URLs |
| `Filter/RangeParser.php` | Parser für Bereiche/Einzelwerte, Einheitenerkennung, Treffer-Logik (ohne Shop-Abhängigkeiten) |
| `Filter/CharacteristicConfig.php` | Darstellung eines Merkmals (aktiv, Typ, Treffer-Logik, Einheit, Schrittweite, aufgeklappt, Boxen pro Zeile) |
| `Filter/ConfigRepository.php` | Tabelle `feature_sliders_characteristic` lesen/schreiben (gecacht, Plugin-Cache-Gruppe) |
| `Filter/Settings.php` | globale Einstellungen (aktiv, URL-Präfix) |
| `Migrations/Migration20260928120000.php` | Tabelle anlegen, Einstellungen aus 1.1.x übernehmen |
| `Migrations/Migration20260928160000.php` | Spalten `active` und `button_columns` (1.4.0) |
| `adminmenu/templates/characteristics.tpl` | Tab „Merkmale“: Übersicht, Hinzufügen-/Bearbeiten-Dialog mit Live-Vorschau |
| `adminmenu/templates/display_tiles.tpl` | Auswahlkacheln für die Darstellung |
| `frontend/template/boxes/box_filter_characteristics.tpl` | Block-Override Merkmalfilter Seitenleiste |
| `frontend/template/snippets/filter/mobile.tpl` | Block-Override Merkmalfilter im Filter-Dialog |
| `frontend/tpl/slider.tpl` | Regler-Markup (NOVA-Preisfilter) |
| `frontend/tpl/assets.tpl` | bindet CSS/JS einmal pro Seite mit `?v=<Plugin-Version>` ein (nicht über info.xml: dort hängt JTL die Template-Version an, Browser behielten nach Plugin-Updates die alten Dateien) |
| `frontend/tpl/buttons.tpl` | Boxen-Markup (NOVA-`{link}` wie `snippets/filter/characteristic.tpl`) |
| `frontend/js/feature_sliders.js` | noUiSlider-Initialisierung (NOVA liefert noUiSlider mit), idempotent, auch nach AJAX |
| `frontend/css/feature_sliders.css` | Abstand unter dem Regler, Boxen-Optik |
| `frontend/boxes/range_filter.tpl` | leer, nur noch registriert, damit Boxen aus 1.0.x beim Update nicht verwaisen |
