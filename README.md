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
- Der normale Checkbox-Filter desselben Merkmals wird ausgeblendet (abschaltbar).

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

Der Plugin-Ordner im Shop muss `plugins/feature_sliders` heißen (= PluginID, Namespace `Plugin\feature_sliders`):

```bash
git clone git@github.com:Olli0204/weight-slider.git feature_sliders
```


1. Plugin installieren, unter *Einstellungen* das Merkmal wählen (z. B. „Körpergewicht“), Einheit/Schrittweite prüfen.
2. **Desktop-Seitenleiste:** *Darstellung → Boxen* → Seitentyp *Artikelliste* → Box
   „Merkmal-Bereichsfilter (Schieberegler)“ in die linke Seitenleiste an die gewünschte Position setzen.
3. **Mobil / Filter-Dialog:** erscheint automatisch (NOVA-Block `snippets-filter-mobile-filters-generic`).
4. Das bisherige Fremd-Plugin deaktivieren.

## Dateien

| Datei | Zweck |
|---|---|
| `Bootstrap.php` | Filter registrieren, Slider-Daten an Smarty (`$mrfSlider`), Standard-Merkmalfilter ausblenden, Admin-Tab |
| `Filter/RangeFilter.php` | JTL-Filter: SQL-Bedingung, Reglerskala, URLs |
| `Filter/RangeParser.php` | Wert-Parser und Treffer-Logik (ohne Shop-Abhängigkeiten) |
| `Filter/Settings.php` | normalisierte Plugin-Einstellungen |
| `frontend/boxes/range_filter.tpl` | Sidebar-Box (Markup wie NOVA-Preisfilter) |
| `frontend/template/snippets/filter/mobile.tpl` | Block-Override für den Filter-Dialog |
| `frontend/tpl/slider.tpl` | gemeinsames Regler-Markup |
| `frontend/js/feature_sliders.js` | noUiSlider-Initialisierung (NOVA liefert noUiSlider mit), idempotent, auch nach AJAX |
| `frontend/css/feature_sliders.css` | Abstände; Griff-/Balken-Optik kommt aus NOVAs `.noUi-*`-Theme |
| `adminmenu/options/characteristics.php` | dynamische Merkmal-Auswahl für die Einstellung |
