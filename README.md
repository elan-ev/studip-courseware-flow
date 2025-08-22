# CoursewareFlow

CoursewareFlow ist ein Stud.IP-Plugin, mit dem sich Courseware-Lernmaterialien aus einer Veranstaltung in eine andere kopieren und dort mit der Quelle synchron halten lassen. Änderungen an den Ursprungsmaterialien werden automatisch in die Zielveranstaltungen übernommen, sodass Inhalte effizient wiederverwendet und konsistent bereitgestellt werden können.

## Voraussetzungen

* Stud.IP ab Version 5.3 bis einschließlich 5.5

* Aktiviertes Courseware-Plugin in den beteiligten Veranstaltungen

* Admin-Rechte zum Installieren von Plugins

## Installation
### Installation über Release-ZIP (empfohlen)

1. Die aktuelle Release-ZIP von CoursewareFlow herunterladen
2. In Stud.IP als Admin anmelden
3. Administration → Plugins → „Neues Plugin installieren“ öffnen
4. Die heruntergeladene ZIP hochladen
5. Das Plugin aktivieren

### Installation aus dem Quellcode

1. Quellcode von CoursewareFlow auschecken oder herunterladen
2. Abhängigkeiten installieren und Build erzeugen:
```
npm ci
npm run zip
```
Der Befehl npm run zip erstellt automatisch eine Release-ZIP mit allen benötigten Dateien (inkl. Composer-Abhängigkeiten).
Diese ZIP befindet sich anschließend im Projektverzeichnis und kann wie oben beschrieben über die Stud.IP-Adminoberfläche installiert werden.

## Nutzung

Nach der Aktivierung steht CoursewareFlow in Veranstaltungen mit aktiver Courseware zur Verfügung.
Es kann eine Quellveranstaltung festgelegt werden, deren Inhalte in Zielveranstaltungen kopiert und synchronisiert werden. Änderungen an der Quelle werden in die Ziele übertragen.

## Entwicklung

Für lokale Entwicklungszwecke stehen unter anderem folgende Skripte zur Verfügung:
```
npm run dev         # Entwicklungsserver mit Vite starten
npm run build       # Produktionsbuild erstellen
npm run dev-build   # Entwicklungsbuild erzeugen
npm run preview     # Gebautes Plugin in einer lokalen Vorschau starten
```
Pull Requests und Issues sind willkommen.

## Über elan e.V.

Der elan e.V. ist ein gemeinnütziger Verein, der sich auf die Entwicklung und Betreuung von Software für Hochschulen spezialisiert hat. 
Das Team entwickelt und betreut unter anderem Stud.IP-Plugins und weitere für Hochschulen relevante Anwendungen. 
Ziel ist es, Lehrenden und Lernenden die Arbeit mit digitalen Lern- und Verwaltungstools zu erleichtern und Prozesse an Hochschulen effizienter zu gestalten. 
Weitere Informationen finden Sie auf [elan-ev.de](https://elan-ev.de).

## Lizenz

CoursewareFlow ist lizenziert unter der GNU Affero General Public License v3.0 (AGPL-3.0).
