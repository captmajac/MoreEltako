<?php
class GenericEEP extends IPSModule {
	
	// erstellung
	public function Create() {
		// Never delete this line!
		parent::Create ();
		$this->RegisterPropertyString ( "DeviceID", "" );
		$this->RegisterPropertyBoolean ( "DataByte", false);
		
		// modulaufruf ändern
		$Module = $this->GetBuffer("Module");
		if ($Module== "")
		{	// default this Module
			$Module= json_decode(file_get_contents(__DIR__ . '/module.json'), true)["prefix"]; 	// Modul für parent merken
			$this->SetBuffer("Module", $Module);
		}
		$this->RegisterTimer("SearchTime",0,$Module."_TimerEvent(\$_IPS['TARGET']);");

		// Connect to available enocean gateway
		$this->ConnectParent ( "{A52FEFE9-7858-4B8E-A96E-26E15CB944F7}" );
	}
	
	// changes der instanz
	public function ApplyChanges() {
		// Never delete this line!
		parent::ApplyChanges ();

		if ($this->ReadPropertyBoolean("DataByte") == true)
		{
			$this->RegisterVariableInteger ( "Data0", "Data0" );
			$this->RegisterVariableInteger ( "Data1", "Data1" );
			$this->RegisterVariableInteger ( "Data2", "Data2" );
			$this->RegisterVariableInteger ( "Data3", "Data3" );
		}
		else 
		{
			$this->UnregisterVariable("Data0");
			$this->UnregisterVariable("Data1");
			$this->UnregisterVariable("Data2");
			$this->UnregisterVariable("Data3");
		}
		
		// noch bug
		$this->SetReceiveDataFilter(".*\"DeviceID\":".$this->GetID().".*");
		//$this->SetReceiveDataFilter(".*\"DeviceID\":".(int)hexdec($this->ReadPropertyString("DeviceID")).".*");

		
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
			//$ValidDevID = strtoupper(str_pad(dechex ( $data->{'DeviceID'}), 8, 0, STR_PAD_LEFT) );
			
			// copy from moreenocean
			$ID = $data->DeviceID - $data->DeviceID%16 + 1;
			if($ID <= 0)return true;
			$DeviceID = sprintf('%08X',$ID);
			$ValidDevID = $DeviceID;
				
			$this->updateList($ValidDevID, $data);
		}
		else {
			if ($this->ReadPropertyBoolean("DataByte") == true)
			{
				$this->ProcessData ( $data );
			}
		}
	}
	
	// einfache datenauswertung
	private function ProcessData($data) { 
	                                      
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
	
	// start/stop der geräte suche
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
			$this->SetReceiveDataFilter(".*\"DeviceID\":".$this->GetID().".*");
		}
	}
	
	// timer aufruf, geräte suche abgelaufen
	public function TimerEvent() {
		$this->SearchModules("false");
	} 
	
	// auswahl aus der search liste
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
	public function updateList(string $DevID, object $data) {
		// Device Liste als Buffer 
		$values = json_decode($this->GetBuffer("List"));//json_decode( $this );
		
		// fix 64 bit 
		//$DevIDInt = (int)hexdec($DevID);
		//if($DevIDInt & 0x80000000)$DevIDInt -=  0x100000000;
		//$DevID = substr($DevID,8);
		
		$newValue = new stdClass;
		$newValue->ID = $DevID;
		$newValue->Ident = $DevID;						//identifier hier gleich der device id	

		// Add Info alle Daten anzeigen
		$AddInfo = strtoupper(str_pad(dechex ( $data->{'DataByte0'}), 2, 0, STR_PAD_LEFT) );
		$AddInfo = $AddInfo."-".strtoupper(str_pad(dechex ( $data->{'DataByte1'}), 2, 0, STR_PAD_LEFT) );
		$AddInfo = $AddInfo."-".strtoupper(str_pad(dechex ( $data->{'DataByte2'}), 2, 0, STR_PAD_LEFT) );
		$AddInfo = $AddInfo."-".strtoupper(str_pad(dechex ( $data->{'DataByte3'}), 2, 0, STR_PAD_LEFT) );
		$newValue->Reference = $AddInfo;
		
		if (@in_array($newValue->Ident , array_column($values, 'Ident') ) == false)
		{
			$values[] = $newValue;
			
			$jsValues = json_encode($values);
			$this->SetBuffer("List",$jsValues);
			
			$this->UpdateFormField("Actors", "values", $jsValues );
		}
	}
	
	// merge von form.json 
	public function AddConfigurationForm(array $ChildForm, string $NewModule) {
		
		// funktionsaufruf in form ändern ändern
		$Module= json_decode(file_get_contents(__DIR__ . '/module.json'), true);
		$NewModule = $this->GetBuffer("Module");
		
		$file = file_get_contents(__DIR__ . '/form.json');
		$file = str_replace($Module["prefix"]."_", $NewModule."_", $file);
		
		$Form = json_decode($file, true);
				
		// form merges
		if (array_key_exists("elements", $ChildForm) ==false){$ChildForm["elements"] = array();};
		if (array_key_exists("elements", $Form) ==false){$Form["elements"] = array();};
		if (array_key_exists("status", $ChildForm) ==false){$ChildForm["status"] = array();};
		if (array_key_exists("status", $Form) ==false){$Form["status"] = array();};
		if (array_key_exists("actions", $ChildForm) ==false){$ChildForm["actions"] = array();};
		if (array_key_exists("actions", $Form) ==false){$Form["actions"] = array();};
		
		// Arrays ersetzen
		$NewForm = array();
		$NewForm["elements"]= array_merge($ChildForm["elements"], $Form["elements"]);
		$NewForm["status"]= array_merge($ChildForm["status"], $Form["status"]);
		$NewForm["actions"]= array_merge($ChildForm["actions"], $Form["actions"]);
		
		return $NewForm;
	}
	
	// fix copied from MoreEnocean, wg. 64 Bit Pi
 	private function GetID() 
 	{
		$ID = hexdec($this->ReadPropertyString("DeviceID"));
		if(IPS_GetKernelVersion() < 6.3){
			if($ID & 0x80000000)$ID -=  0x100000000;
		}
		return($ID);
 	}
	
}
?>
