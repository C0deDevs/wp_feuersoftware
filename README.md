# Feuersoftware WordPress Plugin

Das **Feuersoftware Plugin** ermöglicht es, Einsätze aus **FeuerSoftware Connect** automatisch in WordPress zu importieren und für Besucher anzuzeigen.

> **Hinweis:** Dieses Plugin ist **kein offizielles Plugin** der Firma **Feuer Software** und steht in keiner direkten Verbindung zu ihr.  
> Es wurde unabhängig entwickelt und nutzt ausschließlich die öffentliche Schnittstelle (API) von Feuer Software.

## 📥 Installation
1. Lade das Plugin als ZIP-Datei herunter.
2. Gehe im WordPress-Adminbereich zu **Plugins → Installieren → Plugin hochladen**.
3. Wähle die ZIP-Datei aus und installiere das Plugin.
4. Aktiviere das Plugin.

## ⚙️ Einrichtung
1. Im WordPress-Adminbereich findest du nun im Menü den Eintrag **Feuer Software**.
2. Öffne **Feuer Software → Einstellungen**.
3. Trage dort deinen **FeuerSoftware Connect API Key** ein und speichere die Einstellungen.

## 🔄 Synchronisierung
- Das Plugin ruft automatisch **alle 5 Minuten** neue Einsätze über die **FeuerSoftware Connect API** ab.
- Falls keine Einsätze vorhanden sind oder eine sofortige Aktualisierung gewünscht ist, kann über **Manuell synchronisieren** jederzeit ein Import angestoßen werden.

## 🔍 Einsätze verwalten
- Unter **Feuer Software → Einsätze** werden alle importierten Einsätze angezeigt.
- Du kannst entscheiden, welche Einsätze **veröffentlicht** werden sollen.
- Nicht veröffentlichte Einsätze sind nur für Administratoren sichtbar.

## 🌐 Einsätze auf der Website anzeigen
1. Erstelle in WordPress eine neue Seite oder bearbeite eine bestehende.
2. Füge den Shortcode ein: [feuer_einsaetze]
3. Veröffentliche oder aktualisiere die Seite.
4. Besucher sehen nun eine Tabelle mit den freigegebenen Einsätzen.

---

## 📄 Lizenz
Dieses Projekt steht unter der [MIT Lizenz](LICENSE).
