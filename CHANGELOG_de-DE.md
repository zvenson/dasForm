# 2.3.1

- Shopware-6.7-Kompatibilität: Das Formular wird nicht mehr über feste Element-IDs (`form-subject`/`form-comment`) gefunden, sondern über die versionsstabilen Feld-Namen (`subject`/`comment`). In 6.7 hatten sich die IDs geändert, wodurch weder die Vorbefüllung noch der Versand griffen.
- Das Befüllen reagiert jetzt per `MutationObserver` genau dann, wenn das Modal-Formular im DOM erscheint — kein festes Zeitfenster mehr, das vor dem Öffnen ablaufen konnte.

# 2.3.0

- Verkaufskanal-/Subshop-Fix: Die Anfrage wird jetzt an die korrekte, kanal­spezifische URL gesendet. Bisher wurde die Formular-Action im JavaScript fest auf `/dasform/inquiry` gesetzt, wodurch der Versand in Subshops mit Domain-/Pfad-Präfix fehlschlug.
- Die URL wird nun serverseitig über `path()` erzeugt (inkl. Sales-Channel-Präfix) und per Data-Attribut ans Storefront-JS übergeben.
- Produktname, Anfragetext und Betreff werden über Data-Attribute statt über `localStorage` ins Formular übernommen — zuverlässige Vorbefüllung auch in Subshops.
- Serverseitige Action-Umschreibung im Kontaktformular nutzt jetzt die exakte, kanalkorrekte URL statt einer Pfad-Teilersetzung.
