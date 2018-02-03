<?
	class FTS12 extends IPSModule
	{
		public function Create() 
		{
			//Never delete this line!
			parent::Create();
			$this->RegisterPropertyString("DeviceID", "");
			$this->RegisterPropertyString("Data0", "");
		}
    
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->RegisterVariableBoolean("Pressed", "Pressed");
			$this->RegisterVariableBoolean("PressedLong", "PressedLong");
			$this->RegisterVariableBoolean("PressedShort", "PressedShort");
	

			//Connect to available enocean gateway
			$this->ConnectParent("{A52FEFE9-7858-4B8E-A96E-26E15CB944F7}");
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
			
			IPS_LogMessage("FTS12 Device ID (HEX)",dechex($data->{'DeviceID'}));
			
			// prüfen ob enocean id gleich der device id ist. in HEX
			// prüfen ob das Datenbyte0 in HEX=50 oder 70 ist. dies unterscheidet FTS12 wippen
			if (strcmp(dechex($data->{'DeviceID'}), $this->ReadPropertyString("DeviceID")) === 0
			   and 
			   strcmp(dechex($data->{'DataByte0'}), $this->ReadPropertyString("Data0")) === 0)
			{
				$this->ProcessPress($data);
			}
			else if (strcmp(dechex($data->{'DeviceID'}), $this->ReadPropertyString("DeviceID")) === 0
			   and 
			   strcmp(dechex($data->{'DataByte0'}), "0") === 0)
			{
				$this->ProcessRelease($data);
			}
			//else IPS_LogMessage("FTS12 Device IDs",
			//					"Enocean DeviceID: " . dechex($data->{'DeviceID'}) . 
			//				    " and SymconModul DeviceID: " . $this->ReadPropertyString("DeviceID") . 
			//					" are not equal");
			
		}
		
		private function ProcessPress($spezData)
		{ 	// daten auswerten taste gedrückt
			IPS_LogMessage("FTS12 Device","gedrückt");
			SetValue($this->GetIDForIdent("Pressed"), true);
			SetValue($this->GetIDForIdent("PressedLong"), false);
			SetValue($this->GetIDForIdent("PressedShort"), false);
			
			// todo zeitpunkt des drückens merken
	
		}

		private function ProcessRelease($spezData)
		{ 	// daten auswerten taste losgelassen
			// prüfen ob vorher pressed auch true war nur dann wurde auch der taster gedrückt
			// wenn eine identische deviceid im datenbyte0 mit 50 und eine mit 70 gleichzeitig gedrückt werden kann eine unschärfe entstehen
			IPS_LogMessage("FTS12 Device","losgelassen");
			
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
		private function SetValueFloat($Ident, $value)
		{
			$id = $this->GetIDForIdent($Ident);
			SetValueFloat($id, floatval($value));
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
		
		private function String2Hex($daten)
		{
			$hex = '';
			for($i=0; $i<strlen($daten); $i++){
				$hex .= sprintf("%02X ", ord($daten[$i]));
			}
			return $hex;
		}

		private function Hex2String($hex)
		{
			$string='';
			for ($i=0; $i < strlen($hex)-1; $i+=2){
			$string .= chr(hexdec($hex[$i].$hex[$i+1]));
			}
			return $string;
		}
	}
?>
