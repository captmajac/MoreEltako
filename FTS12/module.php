<?
require_once __DIR__ . '/../Generic/module.php';  // Base Module.php

class FTS12 extends GenericEEP
	{
		protected static $valuesRef =array(
				"10"=>"unten links (0x10)" ,
				"30"=>"oben links (0x30)" ,
				"50"=>"unten rechts (0x50)" ,
				"70"=>"oben rechts (0x70))"
		);	
		
		public function Create() 
		{
			$this->RegisterPropertyString("Data0X00", "");
	
			//Connect to available enocean gateway
			$this->ConnectParent("{A52FEFE9-7858-4B8E-A96E-26E15CB944F7}");
			
			//Never delete this lines!
			$Module= json_decode(file_get_contents(__DIR__ . '/module.json'), true); 	// Modul für parent merken
			$this->SetBuffer("Module", $Module["prefix"]);
			parent::Create();
		}
    
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->RegisterVariableBoolean("Pressed", "Pressed");
			$this->RegisterVariableBoolean("PressedLong", "PressedLong");
			$this->RegisterVariableBoolean("PressedShort", "PressedShort");
			
			//$this->SetReceiveDataFilter(".*\"DeviceID\":".(int)hexdec($this->ReadPropertyString("DeviceID")).".*");
		}
		
		/*
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC through:
		*
		* IOT_Send($id, $text);
		*
		*public function Send($Text)
		*{
		*	$this->SendDataToParent(json_encode(Array("DataID" => "{B87AC955-F258-468B-92FE-F4E0866A9E18}", "Buffer" => $Text)));
		*}
   		*/
		
		public function ReceiveData($JSONString)
		{
			//Never delete this line!
			parent::ReceiveData($JSONString);	// muss aufgerufen werden damit die device suche geht
			
			$data = json_decode($JSONString);
			$this->SendDebug("EnoceanGatewayData", $JSONString, 0);
				
			// prüfen 
			// ob das Datenbyte0 in HEX=50 oder 70 ist. dies unterscheidet FTS12 wippen
			if (strcmp(dechex($data->{'DataByte0'}), $this->ReadPropertyString("Data0X00")) === 0)
			{
				$this->ProcessPress($data);
			}
			// prüfen ob Datenbyte0=0 ist dann Taste losgelassen
			// vorher prüfen ob pressed auch true war nur dann kann der taster erkannt werden
			// hinweis: dennoch nicht ganz safe wenn die taster mit unterschiedlichen bytes gleichzeitig gedrück werden
			else if (strcmp(dechex($data->{'DataByte0'}), "0") === 0
			and GetValue($this->GetIDForIdent("Pressed"))==true)
			{
				$this->ProcessRelease($data);
			}			
		}
		
		private function ProcessPress($Data)
		{ 	// daten auswerten ->taste gedrückt
			SetValue($this->GetIDForIdent("Pressed"), true);
			SetValue($this->GetIDForIdent("PressedLong"), false);
			SetValue($this->GetIDForIdent("PressedShort"), false);
		}

		private function ProcessRelease($Data)
		{ 	// daten auswerten ->taste losgelassen
			
			// zeit different ausrechnen für kurzer langer tastendruck
			$diff= microtime(true) - IPS_GetVariable($this->GetIDForIdent("Pressed"))['VariableUpdated'];
			
			if (GetValue($this->GetIDForIdent("Pressed"))==true)
			{    
				SetValue($this->GetIDForIdent("Pressed"), false);
			}
			// zeitpunkt loslassen auswerten und lange oder kurzen tastendruck aktualisieren
			if ($diff >2)	
				SetValue($this->GetIDForIdent("PressedLong"), true);
			else
				SetValue($this->GetIDForIdent("PressedShort"), true);
			
				
		}
		
		protected function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
		{
			if (!IPS_VariableProfileExists($Name))
			{
				IPS_CreateVariableProfile($Name, 2);
			}
			else
			{
				$profile = IPS_GetVariableProfile($Name);
				if ($profile['ProfileType'] != 2)
					throw new Exception("Variable profile type does not match for profile " . $Name);
			}
			IPS_SetVariableProfileIcon($Name, $Icon);
			IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
			IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
			IPS_SetVariableProfileDigits($Name, $Digits);
			
		}
		
		
		// form auslesen und dann dynamisch erweitern
		public function GetConfigurationForm() {			
			$Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
			
			//Never delete this lines!
			$Module= json_decode(file_get_contents(__DIR__ . '/module.json'), true); 	// merge der form.json
			$NewForm = parent::AddConfigurationForm($Form, $Module["prefix"]);
			return json_encode($NewForm);
		}
		
		
		// überschreiben um nur Tasterinstanzen mit 10,30,50,70 anzuzeigen
		public function SetSelectedModul(object $List) {			
			@$DataByte = $List["Reference"]; 		// Error bei keiner Auswahl
			
			if ($DataByte!=null)
			{		
				IPS_SetProperty ($this->InstanceID, "Data0X00", "".array_search($DataByte, static::$valuesRef));
			}
			
			//Never delete this line!
			parent::SetSelectedModul($List);
		}
		
		// entscheiden was in der Liste aufgenommen werden soll
		// z.b. Filter auf spezielle Geräte oder EEPs dann muss auch $data ausgewertet werden
		// hier überschreiben um nur Tasterinstanzen mit 10,30,50,70 anzuzeigen
		public function updateList(string $DevID, object $data) {
									
			// Device Liste als Buffer
			$values = json_decode($this->GetBuffer("List"));//json_decode( $this );
			
			$DB=dechex($data->{'DataByte0'});
			if (array_key_exists($DB, static::$valuesRef))							// in keys 
			{
				$newValue = new stdClass;
				// fix 64 bit 
				if($DevID & 0x80000000)$DevID -=  0x100000000;
				echo $DevID."\n";
				$newValue->ID = $DevID;
				$newValue->Ident = $DevID."".$DB;							// identifier hier gleich der device id + Datenbyte <>00
				$newValue->Reference = static::$valuesRef[$DB]; 			// hier ggf. nach schon eingesetzter Enocean Referenz suchen
				
				if (@in_array($newValue->Ident , array_column($values, 'Ident') ) == false)
				{
					$values[] = $newValue;
					
					$jsValues = json_encode($values);
					$this->SetBuffer("List",$jsValues);
					
					$this->UpdateFormField("Actors", "values", $jsValues );
				}
			}
			
			
		}
	}
?>
