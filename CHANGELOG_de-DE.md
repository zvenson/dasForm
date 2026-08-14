# 2.5.1

- Auf Touchgeräten ist der Tooltip **aus**; stattdessen steht direkt unter beiden Buttons eine kompakte Zeile: „Finanzieren ab X € – Details auf Anfrage" mit der günstigsten der drei Varianten. Dort gibt es kein Überfahren, der Tooltip war damit ohnehin unerreichbar.
- Umgeschaltet wird über `(hover: none)`, nicht über die Bildschirmbreite — ein Touch-Notebook mit Maus behält also den Tooltip, ein breites Tablet bekommt die Zeile. Die Umschaltung ist eine reine Media-Query; das Storefront-Plugin rührt den Tooltip auf Touchgeräten gar nicht erst an.
- Der Betrag in der kompakten Zeile folgt der Preisdarstellung des Verkaufskanals: Zeigt der Shop Bruttopreise, steht dort der Bruttobetrag — sonst stünde direkt unter einem Bruttopreis eine Nettorate.

# 2.5.0

- **Ratenvorschau als Tooltip**: Beim Überfahren des Finanzierungs-Buttons erscheinen die möglichen Monatsraten der drei FLEX-Varianten, jeweils netto und brutto, dazu Kaufanrechnung und frühester Rückgabemonat. Abschaltbar über die neue Konfigurationskarte „Finanzierungsraten".
- Der Tooltip wird vom Storefront-Plugin an `<body>` gehängt und per `position: fixed` platziert — dieselbe Technik wie die Preis-Tooltips in SvenDasMiet. Absolut positioniert im Buy-Widget bliebe er an einem Vorfahren mit `overflow: hidden` hängen.
- Der Preisbereich der Vorschau ist konfigurierbar (Voreinstellung 300 € bis 30.000 € netto). Zum Testen mit günstigen Artikeln lässt sich der Mindestpreis auf 0 setzen.
- Die Berechnung folgt der Spezifikation des Anbieters centgenau: durchgehend ganzzahlige Cent, `amort` als ganzzahliger Bruch statt als Gleitkommazahl, kaufmännische Rundung (halbe Cent aufwärts), und die Umsatzsteuer auf den bereits gerundeten Rate-Betrag. Gegen eine unabhängige Implementierung über den gesamten Bereich 300 € bis 30.000 € in Einercent-Schritten geprüft: keine Abweichung. Zum Vergleich: dieselbe Rechnung in `double` weicht dort je nach Variante 7.425 bis 15.469 mal um einen Cent ab.
- Gerechnet wird auf den **Nettopreis**; führt der Verkaufskanal Bruttopreise, wird der Nettobetrag über den Steuerzustand ermittelt. Ausserhalb von 300 € bis 30.000 € netto erscheint keine Rate.

# 2.4.4

- Neue Einstellung **„Eckenradius der Schaltflächen"** (Konfigurationskarte „Schaltflächen"). Leer lassen heißt weiterhin: das Theme bestimmt den Radius — das bleibt der Normalfall. Nötig ist die Angabe nur, wenn ein Theme seinen Kaufen-Button über einen Selektor rundet, der auf unsere Buttons nicht zutrifft (etwa `button.btn-buy` in einer custom.css, während unsere Buttons `<a>`-Elemente sind). Der Wert wird inline gesetzt und schlägt damit jede Stylesheet-Regel; erlaubt sind einfache Längen wie `20px`, `0.5rem` oder `50rem`.

# 2.4.3

- Nachbesserung zu 2.4.2: Die Buttons tragen jetzt `btn btn-primary btn-buy`, also exakt die Klassen des Kaufen-Buttons. Mit `btn` allein erbten sie den *globalen* Button-Radius des Themes und wurden in Themes mit runden Kaufen-Buttons eckig, weil die Rundung dort an der spezifischeren Klasse hängt.
- **Höhe korrigiert**: Die Buttons sind jetzt exakt so hoch wie „In den Warenkorb". In Shopware 6.7 steckt die Button-Höhe vollständig in `$btn-line-height` (2,125rem = 34px, plus 2×2px Padding und 2×1px Rahmen = 40px) — das Padding beträgt nur 2px. Das Plugin hatte am Beschriftungs-Element eine eigene `line-height` gesetzt und den Button damit um 14px geschrumpft. Es setzt jetzt keine der größenbestimmenden Eigenschaften mehr (`line-height`, `padding`, `border-radius`, `min-height`, `font-size`), sondern nur noch Farbe und Anordnung.
- Die Icons laufen in der Basisgröße des Themes statt in `icon-sm` und sitzen damit bündig zur Beschriftung.

# 2.4.2

- Die Buttons tragen jetzt die `btn`-Klasse des Themes. Eckenradius, Innenabstand, Schrift und Fokusring kommen damit aus dem Theme des jeweiligen Shops und passen automatisch zu „In den Warenkorb" — auch bei Themes mit runden Pill-Buttons. Bisher war der Radius fest auf 8px gesetzt und wirkte in solchen Themes wie ein Fremdkörper. Eigen bleiben nur Farbe und Anordnung.

# 2.4.1

- Die Datenschutz-Bestätigung wird nur noch dann verlangt, wenn der Shop sie auch anzeigt. Shopware rendert die Checkbox ausschließlich bei aktivem `core.loginRegistration.requireDataProtectionCheckbox` (Einstellungen → Login & Registrierung); ist die Einstellung aus, erscheint nur der Hinweistext. Der Controller hat die Bestätigung bisher trotzdem erzwungen — in solchen Shops liess sich die Anfrage überhaupt nicht absenden, sie scheiterte immer an „Bitte bestätigen Sie die Datenschutzbestimmungen".

# 2.4.0

- **Globale Aktivierung**: Zwei neue Schalter in den Grundeinstellungen blenden „Produktanfrage" bzw. „Finanzierungsanfrage" bei *allen* Produkten ein, ohne dass am einzelnen Produkt ein Haken gesetzt werden muss. Ist der Schalter aus, entscheidet weiterhin das Zusatzfeld am Produkt.
- **Zweiter Button „Finanzierungsanfrage"**: pro Produkt separat ein-/ausschaltbar, mit eigenem Button-Text und eigenem Betreff (drei neue Zusatzfelder). In der Plugin-Konfiguration lässt sich zusätzlich ein eigener Empfänger für Finanzierungsanfragen hinterlegen — leer bedeutet: gleicher Empfänger wie bei Produktanfragen.
- Beide Buttons haben jetzt Icons aus dem Shopware-Icon-Set (`envelope` für die Produktanfrage, `euro` für die Finanzierung).
- Neue Konfigurationskarte „Schaltflächen": Die Farbe beider Buttons ist per Farbwähler einstellbar. Die Farbe wird als CSS-Variable inline gesetzt — eine Farbänderung braucht daher keinen Theme-Build.
- Buttons wurden neu gestaltet: abgerundet, mit Hover-/Fokus-Zuständen; auf Mobilgeräten untereinander, ab dem Medium-Breakpoint nebeneinander mit gleicher Breite.
- **Varianten-Fix**: Ein am Hauptprodukt gesetzter Haken wirkt jetzt auch auf dessen Varianten. Bisher wurde im Template auf `page.product.parent` zugegriffen, das auf der Produktdetailseite nie geladen ist; zudem vererbt Shopware `customFields` nur als ganzen Block, sodass eigene Zusatzfelder der Variante die Werte des Hauptprodukts verdeckten. Ein neuer `ProductPageSubscriber` löst die Werte jetzt serverseitig auf und lädt das Hauptprodukt bei Bedarf nach.
- Die Zusatzfelder werden jetzt auch beim Aktivieren des Plugins angelegt. Bisher geschah das nur bei `install()`/`update()` — wurde das Feldset nie erzeugt (z. B. weil die DB die aktuelle Version bereits kannte und `plugin:update` nichts tat), fehlte der Reiter „Produktanfrage" im Produkt dauerhaft und der Anfrage-Button konnte nirgends aktiviert werden.
- Die Anfrageart wird als Query-Parameter an der Formular-Action übergeben, damit auch ohne JavaScript der richtige Empfänger und die richtige Betitelung der Mail greifen.

# 2.3.1

- Shopware-6.7-Kompatibilität: Das Formular wird nicht mehr über feste Element-IDs (`form-subject`/`form-comment`) gefunden, sondern über die versionsstabilen Feld-Namen (`subject`/`comment`). In 6.7 hatten sich die IDs geändert, wodurch weder die Vorbefüllung noch der Versand griffen.
- Das Befüllen reagiert jetzt per `MutationObserver` genau dann, wenn das Modal-Formular im DOM erscheint — kein festes Zeitfenster mehr, das vor dem Öffnen ablaufen konnte.

# 2.3.0

- Verkaufskanal-/Subshop-Fix: Die Anfrage wird jetzt an die korrekte, kanal­spezifische URL gesendet. Bisher wurde die Formular-Action im JavaScript fest auf `/dasform/inquiry` gesetzt, wodurch der Versand in Subshops mit Domain-/Pfad-Präfix fehlschlug.
- Die URL wird nun serverseitig über `path()` erzeugt (inkl. Sales-Channel-Präfix) und per Data-Attribut ans Storefront-JS übergeben.
- Produktname, Anfragetext und Betreff werden über Data-Attribute statt über `localStorage` ins Formular übernommen — zuverlässige Vorbefüllung auch in Subshops.
- Serverseitige Action-Umschreibung im Kontaktformular nutzt jetzt die exakte, kanalkorrekte URL statt einer Pfad-Teilersetzung.
