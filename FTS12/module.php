<?
require_once __DIR__ . '/../Generic/module.php';  // Base Module.php

class FTS12 extends GenericEEP
	{
		public function Create() 
		{
			//Never delete this line!
			parent::Create();
			$this->RegisterPropertyString("0X00", "");
			
			//Connect to available enocean gateway
			$this->ConnectParent("{A52FEFE9-7858-4B8E-A96E-26E15CB944F7}");
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
			parent::ReceiveData($JSONString);
			
			$data = json_decode($JSONString);
			$this->SendDebug("EnoceanGatewayData", $JSONString, 0);
			
			//IPS_LogMessage("FTS12 Device ID (HEX)",dechex($data->{'DeviceID'}));
			//IPS_LogMessage("FTS12 Data0 (HEX)",dechex($data->{'DataByte0'}));
			
			// prüfen 
			// ob das Datenbyte0 in HEX=50 oder 70 ist. dies unterscheidet FTS12 wippen
			if (strcmp(dechex($data->{'0X00'}), $this->ReadPropertyString("0X00")) === 0)
			{
				$this->ProcessPress($data);
			}
			// prüfen ob Datenbyte0=0 ist dann Taste losgelassen
			// vorher prüfen ob pressed auch true war nur dann wurde kann der taster sicherer erkannt werden
			// hinweis: dennoch nicht ganz safe wenn die taster mit byte 50/70 gleichzeitig gedrück werden
			else if (strcmp(dechex($data->{'0X00'}), "0") === 0
			and GetValue($this->GetIDForIdent("Pressed"))==true)
			{
				$this->ProcessRelease($data);
			}			
		}
		
		private function ProcessPress($Data)
		{ 	// daten auswerten ->taste gedrückt
			IPS_LogMessage("FTS12 Device","gedrückt");
			SetValue($this->GetIDForIdent("Pressed"), true);
			SetValue($this->GetIDForIdent("PressedLong"), false);
			SetValue($this->GetIDForIdent("PressedShort"), false);
		}

		private function ProcessRelease($Data)
		{ 	// daten auswerten ->taste losgelassen
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
		
		// form auslesen und dann dynamisch erweitern
		public function GetConfigurationForm() {
			
			$Form = json_decode(file_get_contents(__DIR__ . '/../form.json'), true);
			
			$NewForm = parent::AddConfigurationForm($Form);
			return json_encode($NewForm);
		}
		
	}
?>
