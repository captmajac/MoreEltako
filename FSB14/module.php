<?
	class FSB14 extends IPSModule
	{
		public function Create() 
		{
			//Never delete this line!
			parent::Create();
			$this->RegisterPropertyString("DeviceIDRet", "");
			$this->RegisterPropertyString("DeviceIDActor", "");
			$this->RegisterPropertyString("Pos0", "");
			$this->RegisterPropertyString("Pos25", "");
			$this->RegisterPropertyString("Pos50", "");
			$this->RegisterPropertyString("Pos75", "");
			$this->RegisterPropertyString("Pos99", "");
			$this->RegisterPropertyString("Pos100", "");
			$this->RegisterPropertyString("Schleppfaktor", "");
		}
    
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->RegisterVariableInteger("Fahrzeit", "Fahrzeit");
			$this->RegisterVariableInteger("Positon", "Positon", "~ShutterAssociation");
			
			$this->EnableAction("Positon");
			

			//Connect to available enocean gateway
			$this->ConnectParent("{A52FEFE9-7858-4B8E-A96E-26E15CB944F7}");
			
			$this->SetReceiveDataFilter(".*\"DeviceID\":".hexdec($this->ReadPropertyString("DeviceIDRet")).".*");
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
			$data = json_decode($JSONString);
			$this->SendDebug("EnoceanGatewayData", $JSONString, 0);
			
			//IPS_LogMessage("FSB14 Device ID (HEX)",dechex($data->{'DeviceID'}));
			
			// fahrzeit auswerten
			$this->Process($data);
			
		}
		
		private function Process($Data)
		{ 	// daten auswerten
			// mögliche Endabschlatung im byte0 mit 70=oben 50=unten
			$db0 = dechex($Data->{'DataByte0'});
		
			//IPS_LogMessage("FSB14 Byte0",$db0);
			//IPS_LogMessage("FSB14 Byte1",$db0);
			
			if (strcmp($db0,"70")===0)
			{	// endmeldung oben
				SetValue($this->GetIDForIdent("Fahrzeit"), 0);
				SetValue($this->GetIDForIdent("Positon"), 0);
			}
			else if (strcmp($db0,"50")===0)
			{	// endmeldung unten
				SetValue($this->GetIDForIdent("Fahrzeit"), $this->ReadPropertyString("Pos100"));
				SetValue($this->GetIDForIdent("Positon"), 100);				
			}
			else if (strcmp($db0,"a")===0)
			{	// angehalten ohne endelage
				// je nach richtung fahrzeit neu berechnen
				$letztezeit=GetValue($this->GetIDForIdent("Fahrzeit"));
				$fahrzeit=$Data->{'DataByte2'} + $Data->{'DataByte3'}*255;
				if ($Data->{'DataByte1'}==2)
				{	// runter
					SetValue($this->GetIDForIdent("Fahrzeit"), $letztezeit+$fahrzeit);
				}
				else if ($Data->{'DataByte1'}==1)
				{	// hoch mit korrektur sleppfaktor der fahrzeit
					SetValue($this->GetIDForIdent("Fahrzeit"), $letztezeit-($fahrzeit*$this->ReadPropertyString("Schleppfaktor")));
				}
				$this->CalcPosition($Data);
			}
				
		}

		private function CalcPosition($Data)
		{ 	// position anhand der fahrzeit und vorgabedaten ungefair bestimmen
			$letztezeit=GetValue($this->GetIDForIdent("Fahrzeit"));
			//$schleppfaktor=1.19;	// position um den schleppfaktor korrigieren wenn die fahrt nach oben war
			
			if ($letztezeit > $this->ReadPropertyString("Pos100"))
			{	// höher als das ende. dann komplett unten
				IPS_LogMessage("FSB14 Pos",">100");
				SetValue($this->GetIDForIdent("Positon"), 100);
			}
			else if ($letztezeit > $this->ReadPropertyString("Pos99"))
			{	// höher als das 99. dann zwischen 99 und 100
				IPS_LogMessage("FSB14 Pos",">99");
				SetValue($this->GetIDForIdent("Positon"), 99);
			}
			else if ($letztezeit > $this->ReadPropertyString("Pos75"))
			{	// höher als das 75. dann zwischen 75 und 99
				IPS_LogMessage("FSB14 Pos",">75");
				$step = $this->ReadPropertyString("Pos99") - $this->ReadPropertyString("Pos75");
				$step2 = ($letztezeit - $this->ReadPropertyString("Pos75")) * (25/$step) + 75;
				SetValue($this->GetIDForIdent("Positon"), $step2);
			}
			else if ($letztezeit > $this->ReadPropertyString("Pos50"))
			{	// höher als das 50. dann zwischen 50 und 75
				IPS_LogMessage("FSB14 Pos",">50");
				$step = $this->ReadPropertyString("Pos75") - $this->ReadPropertyString("Pos50");
				$step2 = ($letztezeit - $this->ReadPropertyString("Pos50")) * (25/$step) + 50;
				SetValue($this->GetIDForIdent("Positon"), $step2);
			}
			else if ($letztezeit > $this->ReadPropertyString("Pos25"))
			{	// höher als das 25. dann zwischen 25 und 50
				IPS_LogMessage("FSB14 Pos",">25");
				$step = $this->ReadPropertyString("Pos50") - $this->ReadPropertyString("Pos25");
				$step2 = ($letztezeit - $this->ReadPropertyString("Pos25")) * (25/$step) + 25;
				SetValue($this->GetIDForIdent("Positon"), $step2);
			}
			else if ($letztezeit > $this->ReadPropertyString("Pos0"))
			{	// höher als das 0. dann zwischen 0 und 25
				IPS_LogMessage("FSB14 Pos",">0");
				$step = $this->ReadPropertyString("Pos25") - $this->ReadPropertyString("Pos0");
				$step2 = ($letztezeit - $this->ReadPropertyString("Pos0")) * (25/$step) + 0;
				SetValue($this->GetIDForIdent("Positon"), $step2);
			}
			else if ($letztezeit < $this->ReadPropertyString("Pos0"))
			{	// kleiner als das 0. dann ganz oben
				IPS_LogMessage("FSB14 Pos","<0");
				SetValue($this->GetIDForIdent("Positon"), 0);
			}
			
		}

		public function RequestAction($Ident, $Value)
		{
			switch($Ident) {
			case "Positon":
				// Fahrzeit berechnen
				// value aus Profil muss es als Positions konfiguration geben
				$zielzeit= $this->ReadPropertyString("Pos".$Value);
				// Zielzeit - aktuell gespeicherte zeit ist die fahrzeit
				$fahraenderung = abs($zielzeit - GetValue($this->GetIDForIdent("Fahrzeit")));
				IPS_LogMessage("FSB14 Fahränderung",$fahraenderung);
				
				IPS_LogMessage("FSB14 Link",$this->ReadPropertyString("DeviceIDActor"));
						
				// Neuen Wert in die Statusvariable schreiben, wird ggf über die Rückmeldung korrigiert
				// SetValue($this->GetIDForIdent($Ident), $Value);
				break;
			}
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
		
		protected function SendDebug($Message, $Data, $Format)
		{
			if (is_array($Data))
			{
			    foreach ($Data as $Key => $DebugData)
			    {
						$this->SendDebug($Message . ":" . $Key, $DebugData, 0);
			    }
			}
			else if (is_object($Data))
			{
			    foreach ($Data as $Key => $DebugData)
			    {
						$this->SendDebug($Message . "." . $Key, $DebugData, 0);
			    }
			}
			else
			{
			    parent::SendDebug($Message, $Data, $Format);
			}
		} 
		
	
	}
?>
