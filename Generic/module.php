<?php
class GenericEEP extends IPSModule {
	
	public function Create() {
		// Never delete this line!
		parent::Create ();
		$this->RegisterPropertyString ( "DeviceID", "" );
		//$this->RegisterPropertyString ( "EEP", "" );


		// Connect to available enocean gateway
		$this->ConnectParent ( "{A52FEFE9-7858-4B8E-A96E-26E15CB944F7}" );
	}
	public function ApplyChanges() {
		// Never delete this line!
		parent::ApplyChanges ();

		$this->RegisterVariableInteger ( "Data0", "Data0" );
		$this->RegisterVariableInteger ( "Data1", "Data1" );
		$this->RegisterVariableInteger ( "Data2", "Data2" );
		$this->RegisterVariableInteger ( "Data3", "Data3" );

		// $this->SetReceiveDataFilter(".*\"DeviceID\":".(int)hexdec($this->ReadAttributeString("DeviceID")).".*");
	}

	/*
	 * This function will be available automatically after the module is imported with the module control.
	 * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
	 *
	 * IOT_Send($id, $text);
	 *
	 * public function Send($Text)
	 * {
	 * $this->SendDataToParent(json_encode(Array("DataID" => "{B87AC955-F258-468B-92FE-F4E0866A9E18}", "Buffer" => $Text)));
	 * }
	 */
	public function ReceiveData($JSONString) {
		$data = json_decode ( $JSONString );
		$this->SendDebug ( "EnoceanGatewayData", $JSONString, 0 );

		$state = $this->GetBuffer("Serach") ;
		if ($state=="true")
		{
			$tmp = $this->GetBuffer("Test");
			$tmp = $tmp.",".dechex ( $data->{'DeviceID'} );
			IPS_LogMessage ( "FTS12 Device ID (HEX)", $tmp );
			$this->SetBuffer("Test",$tmp);
			
			// $this->UpdateFormField("Actors", "values", "json string");
			//$this->ReloadForm();
		}
		else {
			$this->ProcessData ( $data );
		}
	}
	
	private function ProcessData($data) { // daten auswerten ->taste gedrückt
	                                      
		SetValue ( $this->GetIDForIdent ( "Data0" ), $data->{'DataByte0'} );
		SetValue ( $this->GetIDForIdent ( "Data1" ), $data->{'DataByte1'} );
		SetValue ( $this->GetIDForIdent ( "Data2" ), $data->{'DataByte2'} );
		SetValue ( $this->GetIDForIdent ( "Data3" ), $data->{'DataByte3'} );
	}

	/*
	 * protected function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	 * {
	 * if (!IPS_VariableProfileExists($Name))
	 * {
	 * IPS_CreateVariableProfile($Name, 2);
	 * }
	 * else
	 * {
	 * $profile = IPS_GetVariableProfile($Name);
	 * if ($profile['ProfileType'] != 2)
	 * throw new Exception("Variable profile type does not match for profile " . $Name);
	 * }
	 * IPS_SetVariableProfileIcon($Name, $Icon);
	 * IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	 * IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	 * IPS_SetVariableProfileDigits($Name, $Digits);
	 *
	 * }
	 */
	protected function SendDebug($Message, $Data, $Format) {
		if (is_array ( $Data )) {
			foreach ( $Data as $Key => $DebugData ) {
				$this->SendDebug ( $Message . ":" . $Key, $DebugData, 0 );
			}
		} else if (is_object ( $Data )) {
			foreach ( $Data as $Key => $DebugData ) {
				$this->SendDebug ( $Message . "." . $Key, $DebugData, 0 );
			}
		} else {
			parent::SendDebug ( $Message, $Data, $Format );
		}
	}
	
	public function GetConfigurationForm() {
		
		$Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
		return json_encode($Form);
	}
	
	public function SearchModules(string $state) {
		
		if ($state=="true")
		{
			$this->SetBuffer("Serach", "true");
			$this->SetBuffer("Test","");
		}
		else
		{
			$this->SetBuffer("Serach", "false");
			$this->SetBuffer("Test","");
		}
	}
		
	public function SetSelectedModul(string $DevID) {
		
		$this->SetBuffer("Serach", "false");
		IPS_LogMessage ( "FTS12 Device ID (HEX)", $DevID );
		
		IPS_SetProperty ($this->InstanceID, "DeviceID", "".$DevID);
		IPS_ApplyChanges($this->InstanceID);
		
		
	}
}
?>
