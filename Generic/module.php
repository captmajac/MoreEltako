<?php
class GenericEEP extends IPSModule {
	
	public function Create() {
		// Never delete this line!
		parent::Create ();
		$this->RegisterPropertyString ( "DeviceID", "" );


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
		
		$this->SetReceiveDataFilter(".*\"DeviceID\":".(int)hexdec($this->ReadPropertyString("DeviceID")).".*");
		

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

		//IPS_LogMessage("xxx",$this);
		
		if ($this->GetBuffer("Serach")=="true")
		{
			// führende Nullen und in 8 Zeichen Grossbuchstaben formatieren
			$ValidDevID = strtoupper(str_pad(dechex ( $data->{'DeviceID'}), 8, 0, STR_PAD_LEFT) );
			$this->updateList($ValidDevID);
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
			$this->SetReceiveDataFilter("");
			// TODO: Timer starten für zeitlich begrenze suche
		}
		else
		{
			$this->SetBuffer("Serach", "");
			$this->SetBuffer("Test","");
		}
	}
	

	public function SetSelectedModul(string $DevID) {
		
		$this->SetBuffer("Serach", "");
		$this->SetBuffer("List","");
		IPS_SetProperty ($this->InstanceID, "DeviceID", "".$DevID);
		IPS_ApplyChanges($this->InstanceID);
	}
	
	public function EmptyList(IPSList $List)
	{
		
		try {
			$tmpException = $List["ID"];
			print_r($tmpException);
		} catch (Exception $e) {
			print_r($tmpException);
			return false;
		}
		
		if ($this->GetBuffer("List") == "")
		{
			return true;
		}
		else
		{
			print_r ($List[0]);
			
			return true;
		}
		
	}
	
	public function updateList(string $DevID) {
		
		
		// Device Liste als Buffer 
		$values = json_decode($this->GetBuffer("List"));//json_decode( $this );
		
		$newValue = new stdClass;
		$newValue->ID = $DevID;
		$newValue->Reference = "todo"; 		// hier noch nach schon eingesetzter Enocean Referenz suchen

			
		if (@in_array($newValue->ID , array_column($values, 'ID') ) == false)
		{
			$values[] = $newValue;
			
			$jsValues = json_encode($values);
			$this->SetBuffer("List",$jsValues);
			
			$this->UpdateFormField("Actors", "values", $jsValues );
		}
		

		
	}
	
}
?>
