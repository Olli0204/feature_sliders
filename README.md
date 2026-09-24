# Merkmal-Bereichsfilter (JTL-Shop 5.7 / 5.8)

Schieberegler-Filter für Merkmale, die in der Wawi als Bereich gepflegt sind – z. B. **Körpergewicht** mit
Werten wie „35 - 55 kg“. Der Kunde begrenzt den Bereich links und rechts; angezeigt werden alle Artikel, deren
Bereich sich mit der Auswahl **überschneidet** (die Auswahl muss nicht vollständig im Artikel-Bereich liegen).

## Funktionsweise

- Eigener JTL-Produktfilter (`Filter/RangeFilter.php`, registriert über `HOOK_PRODUCTFILTER_CREATE`), URL-Parameter
  `?mrf=<von>_<bis>` (Name einstellbar). Er kombiniert sich mit allen anderen Filtern, erscheint in den
  aktiven Filtern („Körpergewicht: 50 – 60 kg ×“) und wird von „Alle Filter entfernen“ mit zurückgesetzt.
- Die Merkmalwerte werden in PHP geparst (`Filter/RangeParser.php`) und in eine `kMerkmalWert IN (…)`-Bedingung
  übersetzt – kein eigenes DB-Schema, keine Synchronisation nötig. Wawi-Abgleiche wirken sofort.
- Werte an Variationskindern zählen für den Vaterartikel mit (z. B. Gewichtsbereich je Boardlänge).
- Die Reglerskala ergibt sich aus den Werten der Artikel in der aktuellen Kategorie/Suche (alle anderen aktiven
  Filter berücksichtigt, der eigene nicht).
- Keine Box-Konfiguration: Der Regler ersetzt im NOVA-Merkmalfilter die Checkbox-Werte genau dieses Merkmals
  (Seitenleiste: Block `boxes-box-filter-characteristics-characteristics`, Filter-Dialog:
  `snippets-filter-mobile-filters-collapse`). Titel, Aufklapp-Pfeil, Trennlinie und Position (Merkmal-Sortierung
  der Wawi) bleiben NOVA-Original. Fehlt das Merkmal in der Liste (z. B. 0 Treffer nach dem Eingrenzen), setzt das
  Plugin einen Platzhalter ein, damit der Regler erreichbar bleibt.
- Optik: exakt das Markup des NOVA-Preisfilters (`price-range-inputs`, `col-5`, `input-group-prepend`,
  `price-range-slide`), daher greifen die Theme-Regeln 1:1. Zurückgesetzt wird wie beim Preisfilter über die
  aktiven Filter („Körpergewicht: … ×“) oder indem man den Regler wieder ganz aufzieht.

### Erkannte Schreibweisen

| Wawi-Wert | Bereich |
|---|---|
| `35 - 55 kg`, `35–55kg`, `35 bis 55 kg`, `35,5 - 55 kg` | 35 – 55 |
| `bis 55 kg`, `< 55 kg`, `max. 55 kg` | nach unten offen – 55 |
| `ab 80 kg`, `80+ kg`, `> 80 kg`, `über 80 kg` | 80 – nach oben offen |
| `55 kg` | 55 – 55 |

Werte ohne Zahl werden ignoriert; der Admin-Tab **Werteübersicht** listet alle Werte mit Erkennungsstatus.

### Treffer-Logik (Einstellung)

- **Überschneiden** (Standard): Artikel `35–55` passt zu Auswahl `50–70`, `20–40`, `40–45`, `55–60`.
- **Enthält**: Artikel-Bereich muss die Auswahl vollständig abdecken.
- **Liegt in**: Artikel-Bereich muss vollständig in der Auswahl liegen.

## Einrichtung

Der Plugin-Ordner im Shop muss `plugins/feature_sliders` heißen – der Repo-Name stimmt überein, ein einfacher Clone reicht (= PluginID, Namespace `Plugin\feature_sliders`):

```bash
git clone git@github.com:Olli0204/feature_sliders.git
```


1. Plugin installieren, unter *Einstellungen* das Merkmal wählen (z. B. „Körpergewicht“), Einheit/Schrittweite prüfen.
2. Fertig – der Regler erscheint automatisch im Merkmalfilter (Seitenleiste und Filter-Dialog). Voraussetzung ist
   nur der normale JTL-Merkmalfilter (*Einstellungen → Navigationsfilter → Merkmalfilter benutzen*).
3. Das bisherige Fremd-Plugin deaktivieren.

## Dateien

| Datei | Zweck |
|---|---|
| `Bootstrap.php` | Filter registrieren, Slider-Daten an Smarty (`$mrfSlider`), Merkmal im Merkmalfilter sicherstellen/aufklappen, Admin-Tab |
| `Filter/RangeFilter.php` | JTL-Filter: SQL-Bedingung, Reglerskala, URLs |
| `Filter/RangeParser.php` | Wert-Parser und Treffer-Logik (ohne Shop-Abhängigkeiten) |
| `Filter/Settings.php` | normalisierte Plugin-Einstellungen |
| `frontend/template/boxes/box_filter_characteristics.tpl` | Block-Override Merkmalfilter Seitenleiste |
| `frontend/template/snippets/filter/mobile.tpl` | Block-Override Merkmalfilter im Filter-Dialog |
| `frontend/boxes/range_filter.tpl` | leer, nur noch registriert, damit Boxen aus 1.0.x beim Update nicht verwaisen |
| `frontend/tpl/slider.tpl` | gemeinsames Regler-Markup |
| `frontend/js/feature_sliders.js` | noUiSlider-Initialisierung (NOVA liefert noUiSlider mit), idempotent, auch nach AJAX |
| `frontend/css/feature_sliders.css` | Abstände; Griff-/Balken-Optik kommt aus NOVAs `.noUi-*`-Theme |
| `adminmenu/options/characteristics.php` | dynamische Merkmal-Auswahl für die Einstellung |
