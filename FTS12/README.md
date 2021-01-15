# FTS12
Eltako IPS Modul für FTS12 Aktor (Inspiriert von WLSource/SymconEnoceanModul)

Das Eltakto Tastermodul ist von IPS nicht komfortabel unterstützt. Dieses Modul soll den Tasterstatus als Bool Statusvariablen ausgeben und als Kofortfunktion eine Unterscheidung zwischen einem kurzen und langem Tastendruck ausweisen.

# Variablen
1. Pressed - Ist true solange der Taster festgehalten wird
2. PressedShort - Wird nach dem loslassen des Tasters auf true gesetzt. wenn der Tastendruck kurz (<2 Sek.) war. PressedLong ist dann false. Der Status true bleibt auch stehen bis zum nächsten Tastendruck.
3. PressedLong - Wird true gesetzt wenn der Tastendruck lang (>2 Sek.) war. PressedShort ist dann false. Der Status true bleibt auch stehen bis zum nächsten Tastendruck.

# Konfiguration
Instanz ist unter Eltakto Geräten mit dem Namen FTS12 zu finden

1. Enocean Gateway auswählen

2. Parameter setzen
DeviceID (Hex) - Ist die Enocean Geräteadresse in Hexadecimal mit führende Nullen und 8 Zeichen
Function - Ist eine Eltako FTS Spezialität. Es gibt einen Doppelwippen/Vierwachwippen Eingang bei dem eine Geräte Adresse mehrere Taster abbildet. Diese können nur durch das ein Datenbyte unterschieden werden. In der Auswahlliste ist die Wippenstellung bzw, das Datenbyte zu wähen.
Generate Databyte - Aus der Oberklasse GenericEEP. Wenn aktiviert werden 4 Statusvariablen für die DataBytes angelegt. In der Regel ist für die FTS Funktion dies nicht notwendig.

3. Komfortfunktion Suchen
Die Suche zeigt im PopUp alle potentiell passenden Sender an. Um ein Taster in die Konfiguration ein zu lernen ist die Suche zu öffnen und der Taster zu drücken. Nun sollte in der Liste die ID und die entsprechenden Datenbyte bzw. Wippenstellung angezeigt werden.
Durch Auswählen des Listeneintrages und dann "apply selected" wird der Eintrag in die Konfiguration übernommen.
Die Suche wird 60 Sekunden durchgeführt und unter dem Button angezeigt wenn diese abgelaufen ist.

# Offen
- Ggf. Ausgabe Dauer Tastendrucks
- Prüfen ob es auch für FTS14 Geräte geht
- Lokalisierung

# KnownBugs
- Im PopUP Suchen Dialog wird die Suche nicht beendet wenn man schliessen drückt. Dafür wurde ein Timer eingesetzt. Auch die Daten werden bei Auswahl einer Zeile nicht übernommen
- Laufen sehr viele Geräte in die Suchliste ein, verliert eine markierte Zeile den Focus. Hier muss man schneller sein oder eine Zeit warten bis nicht mehr aktualisiert wird. Soätestens nach 60 Sekunden, wenn die Suche beendet wird.
- Theoretisch kann bei mehreren gleichen Adressen z.b. Vierfachwippe ein ungenaues Schalten erfolgen wenn mehrer Taster der Wippe gleichzeitig gedrückt und losgelassen werden. 
