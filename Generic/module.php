<?php
class GenericEEP extends IPSModule {
	
	public function Create() {
		// Never delete this line!
		parent::Create ();
		$this->RegisterPropertyString ( "DeviceID", "" );
		$this->RegisterTimer("SearchTime",0,"GenericEEP_TimerEvent(\$_IPS['TARGET']);");

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
		
		if ($this->GetBuffer("Serach")=="true")
		{
			// führende Nullen und in 8 Zeichen Grossbuchstaben formatieren
			$ValidDevID = strtoupper(str_pad(dechex ( $data->{'DeviceID'}), 8, 0, STR_PAD_LEFT) );
			$this->updateList($ValidDevID, $data);
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
	

	public function SearchModules(string $state) {
		
		if ($state=="true")
		{
			$this->SetBuffer("Serach", "true");
			$this->SetReceiveDataFilter("");
			$this->UpdateFormField("Actors", "values", "" );
			// Timer starten für zeitlich begrenzte Suche
			$this->SetTimerInterval("SearchTime", 1000*60);
			$this->UpdateFormField("TimeLabel", "caption", "Suche läuft..." );
		}
		else
		{
			$this->SetBuffer("Serach", "");
			$this->UpdateFormField("TimeLabel", "caption", "Suche abgelaufen" );
			$this->SetTimerInterval("SearchTime", 0);
		}
	}
	
	// timer aufruf, suchzeit abgelaufen
	public function TimerEvent() {
		$this->SearchModules("false");
	} 
	
	public function SetSelectedModul(object $List) {

		@$DevID = $List["ID"]; 		// Kommt ein Error bei keiner Auswahl

		$this->SetBuffer("Serach", "");
		$this->SetBuffer("List","");
		$this->SetTimerInterval("SearchTime", 0);
		
		if ($DevID!=null)
		{
			IPS_SetProperty ($this->InstanceID, "DeviceID", "".$DevID);
		}
		// Apply schliesst auch popup
		IPS_ApplyChanges($this->InstanceID);		
	}


	// ggf. auch entscheiden was in der Liste aufgenommen werden soll
	// z.b. Filter auf spezielle Geräte oder EEPs dann muss auch $data ausgewertet werden
	//
	public function updateList(string $DevID, string $data) {
		// Device Liste als Buffer 
		$values = json_decode($this->GetBuffer("List"));//json_decode( $this );
		
		$newValue = new stdClass;
		$newValue->ID = $DevID;
		$newValue->Reference = "not implemented"; 		// hier ggf. nach schon eingesetzter Enocean Referenz suchen

			
		if (@in_array($newValue->ID , array_column($values, 'ID') ) == false)
		{
			$values[] = $newValue;
			
			$jsValues = json_encode($values);
			$this->SetBuffer("List",$jsValues);
			
			$this->UpdateFormField("Actors", "values", $jsValues );
		}
	}
	
	/*
	// form dynamisch erweitern
	public function GetConfigurationForm() {
		
		$Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
		return json_encode($Form);
	}
*/
	
	public function AddConfigurationForm(array $ChildForm) {
		
		$Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
		
		if (array_key_exists("elements", $ChildForm) ==false){$ChildForm["elements"] = array();};
		if (array_key_exists("elements", $Form) ==false){$Form["elements"] = array();};
		if (array_key_exists("status", $ChildForm) ==false){$ChildForm["status"] = array();};
		if (array_key_exists("status", $Form) ==false){$Form["status"] = array();};
		if (array_key_exists("actions", $ChildForm) ==false){$ChildForm["actions"] = array();};
		if (array_key_exists("actions", $Form) ==false){$Form["actions"] = array();};
		
		// Arrays ersetzen
		$NewForm["elements"]= array_merge($ChildForm["elements"], $Form["elements"]);
		$NewForm["status"]= array_merge($ChildForm["status"], $Form["status"]);
		$NewForm["actions"]= array_merge($ChildForm["actions"], $Form["actions"]);
		
		return $NewForm;
	}
	
}
?>
