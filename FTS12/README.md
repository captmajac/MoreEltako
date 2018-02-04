# FTS12
Eltako IPS Modul für FTS12 Aktor (Inspiriert von WLSource/SymconEnoceanModul)

Das Eltakto Tastermodul ist von IPS nicht unterstützt. Dieses Modul soll den Tasterstatus als Bool Statusvariablen ausgeben und eine festlegbare unterscheidung zwischen einem kurzen und langem Tastendruck

# Variablen
1. Pressed - Ist true solange der Taster festgehalten wird
2. PressedShort - Wird nach dem loslassen des Tasters auf true gesetzt wenn der Tastendruck kurz (<2 Sek.) war. PressedLong ist dann false. Der Status true bleibt auch stehen bis zum nächsten Tastendruck.
3. PressedLong - Wird true gesetzt wenn der Tastendruck lang (>2 Sek.) war. PressedShort ist dann false. Der Status true bleibt auch stehen bis zum nächsten Tastendruck.

# Konfiguration
Instanz ist unter Eltakto Geräten mit dem Namen FTS12 zu finden

1. Enocean Gateway auswählen

2. Parameter setzen
DeviceID (Hex) - Ist die Enocean Geräteadresse in Hexadecimal ohne führende Nullen
Data0 (Hex) - Ist eine Eltako FTS Spezialität. Es gibt einen Doppelwippen Eingang bei dem eine Geräte Adresse zwei Taster abbildet. Diese können nur durch das Datenbyte0 unterschieden werden. Im Standard ist dies immer Hex=70. die zweite Doppelwippe kann dann auch Hex=50 sein

# Offen
- Ausgabe Dauer bzw. konfiguration des Tastendrucks
- Automatisches Suchen von Tastern
- Prüfen ob es auch für FTS14 Geräte geht
