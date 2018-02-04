# FSB14
Eltako IPS Modul für FSB14 Aktor

Das Eltakto Beschattungsmodul ist von IPS nicht vollständig unterstützt. Dieses Modul soll den Rückkanalauswerten der Fahrzeit auswerten und damit die Position in Prozent wiedergeben.

Die FSB14 Aktoren senden in der Endlade (Ablauf der max. Zeit am Aktor eingestellt) ein extra Protokoll für die erreichte Endlage. Dieses wird ausgewertet und in diesem Fall die Fahrzeiten und Position gemäß der Fahrzeiteneinstellung korrigiert. Damit erreicht man in einer Endlage immer eine Neujustierung folls bei mehrfachen Fahrten ohne Endlage eine Verschiebung der Position aufgrund von Ungenauigkeiten eintritt. Hintegrund: Der Aktor meldet nur die Fahrzeit und keine echte Position.

# Variablen
1. Fahrzeit - Ist die aggregierte Fahrzeit des Aktors als Integer in Sek*10 (Beispiel 5,5 Sek = 55). Die Fahrzeit wird bei beim hochfahren um einen Schleppfaktor korrigiert.
2. Position - Gibt die Position der Beschattung in Prozent wieder. 0=offen und 100=geschlossen

# Konfiguration
Instanz ist unter Eltakto Geräten mit dem Namen FSB14 zu finden

1. Enocean Gateway auswählen

2. Parameter setzen
- DeviceID (Hex) - Ist die Enocean Geräteadresse in Hexadecimal ohne führende Nullen
Fahrzeiten:
- offen - Sollte 0 sein. Vollstäddig offen
- 25% - Bsp. 50
- 50% - Bsp. 90. Die Fahrzeit in Sek*10 eintragen, wo die Beschattung zur Hälfte beschattet hat. (90= 9 Sek.)
- 75% - Bsp. 140
- 99% - Bsp. 200. Die Fahrzeit in Sek*10 eintragen, wo die Beschattung z.b. Rolläden nur noch die Schlitze offen sind.
- geschlossen - Bsp. 273 - vollständog geschlossen
Fahrzeitkorrektur für fahrten nach oben:
- Schleppfaktor - 1 für keine Korrektur. Bsp. 0.835 für einen 2m Alu Rolladen. Hintergrund ist das aufgrund des Gewichtes die Laufzeit für die Fahrt nach unten und oben unterschiedlich ist. Um den Faktor zu ermitteln ist die Fahrzeit von offen->geschlossen und von geschlossen->offen zu messen. Beispiel runter=24,21 Sek. und hoch=28,81 Sek. Der Schleppfaktor ist dann Zeit runter geteilt durch Zeit hoch. Also 24.21 / 28.81 = 0.84


# Offen
- Ansteuern eines verlinken Rolladen Aktors
- Variablenprofil
