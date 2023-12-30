# Aktualisierungsmelder

Zur Verwendung dieses Moduls als Privatperson, Einrichter oder Integrator wenden Sie sich bitte zunächst an den Autor.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.


### Inhaltsverzeichnis

1. [Modulbeschreibung](#1-modulbeschreibung)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Schaubild](#3-schaubild)
4. [Auslöser](#4-auslöser)
5. [PHP-Befehlsreferenz](#5-php-befehlsreferenz)
   1. [Status aktualisieren](#51-status-aktualisieren)

### 1. Modulbeschreibung

Dieses Modul überwacht Variablen auf Aktualisierung.
Die Prüfung des Aktualisierungszeitraums ist für maximal 21 Tage möglich.

#### Verhalten bei Neustart:  

* Sofortige Prüfung  
Alle Variablen werden sofort auf eine überfällige Aktualisierung geprüft.  


* Prüfung zum nächsten Prüfzeitpunkt    
Wenn bereits eine zuvor überfällige Variable `Alarm` sich wieder aktualisiert `OK`, dann wird die Statusliste um diese Variable aktualisiert.  
Sollten zu diesem Zeitpunkt keine weiteren Variablen mehr überfällig sein, dann wird ebenfalls der Gesamtstaus auf `OK` gesetzt.
Wird eine neue Variable überfällig, so wird diese erst zum nächsten Prüfzeitpunkt berücksichtigt, welcher der vom Benutzer festgelegte Aktualisierungszeitraum ist.

### 2. Voraussetzungen

- IP-Symcon ab Version 6.1

### 3. Schaubild

```
                      +----------------------------------+
                      | Aktualisierungsmelder (Modul)    |
                      |                                  |
Auslöser------------->+ Status                           |
                      +----------------------------------+
```

### 4. Auslöser

Das Modul Aktualisierungsmelder reagiert auf verschiedene Variablen als Auslöser.

### 5. PHP-Befehlsreferenz

#### 5.1 Status aktualisieren

```text
AM_UpdateStatus(integer INSTANCE_ID);
```

Liefert keinen Rückgabewert.

| Parameter     | Beschreibung   | 
|---------------|----------------|
| `INSTANCE_ID` | ID der Instanz |


**Beispiel:**
```php
AM_UpdateStatus(12345);
```
