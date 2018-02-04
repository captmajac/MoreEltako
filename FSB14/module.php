<?
	class FSB14 extends IPSModule
	{
		public function Create() 
		{
			//Never delete this line!
			parent::Create();
			$this->RegisterPropertyString("DeviceIDRet", "");
			$this->RegisterPropertyString("Pos0", "");
			$this->RegisterPropertyString("Pos25", "");
			$this->RegisterPropertyString("Pos50", "");
			$this->RegisterPropertyString("Pos75", "");
			$this->RegisterPropertyString("Pos99", "");
			$this->RegisterPropertyString("Pos100", "");
		}
    
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->RegisterVariableInteger("Fahrzeit", "Fahrzeit");
			$this->RegisterVariableInteger("Positon", "Positon");
			//$this->RegisterVariableBoolean("PressedShort", "PressedShort");
	

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
			// mögliche Endabschlatung über zeit 70=oben 50=unten
			$db0 = dechex($data->{'DataByte0'});
			if (strcmp($db0,"50")===0)
			{	// endmeldung oben
				SetValue($this->GetIDForIdent("Fahrzeit"), 0);
				SetValue($this->GetIDForIdent("Position"), 0);
			}
			else if (strcmp($db0,"70")===0)
			{	// endmeldung unten
				SetValue($this->GetIDForIdent("Fahrzeit"), $this->ReadPropertyString("Pos100"));
				SetValue($this->GetIDForIdent("Position"), 100);				
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
