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
