<?php
class googlePlaces
{
	public $_outputType = "json"; //either json, xml or array
	public $_errors = array();
		
	protected $_apiKey = '';
	protected $_apiUrl = "https://maps.googleapis.com/maps/api/place";
	protected $_apiCallType = '';
	protected $_includeDetails = false;
	protected $_language = 'en';
	protected $_location; // Required - This must be provided as a google.maps.LatLng object.
	protected $_radius; // Required
	protected $_types; // Optional - separate tyep with pipe symbol http://code.google.com/apis/maps/documentation/places/supported_types.html
	protected $_name; // Optional
	protected $_sensor = 'false'; // Required - is $Location coming from a sensor? like GPS?
	protected $_reference;
	protected $_accuracy;
	
	public function __construct($apiKey)
	{
		$this->_apiKey = $apiKey;	
	}

	public function search()
	{
		$this->_apiCallType = "search";

		return $this->_apiCall();
	}

	public function details()
	{
		$this->_apiCallType = "details";
		
		return $this->_apiCall();
	}

	public function checkIn()
	{
		$this->_apiCallType = "checkin-in";

		return $this->_apiCall();
	}

	public function add()
	{
		$this->_apiCallType = "add";

		return $this->_apiCall();
	}

	public function delete()
	{
		$this->_apiCallType = "delete";

		return $this->_apiCall();
	}

	public function setLocation($location)
	{
		$this->_location = $location;
	}

	public function setRadius($radius)
	{
		$this->_radius = $radius;
	}

	public function setTypes($types)
	{
		$this->_types = $types;
	}

	public function setLanguage($language)
	{
		$this->_language = $language;
	}

	public function setName($name)
	{
		$this->_name = $name;
	}

	public function setSensor($sensor)
	{
		$this->_sensor = $sensor;
	}

	public function setReference($reference)
	{
		$this->_reference = $reference;
	}

	public function setAccuracy($accuracy)
	{
		$this->_accuracy = $accuracy;
	}

	public function setIncludeDetails($includeDetails)
	{
		$this->_includeDetails = $includeDetails;
	}

	protected function _checkErrors()
	{
		if(empty($this->_apiCallType)) {
			$this->_errors[] = "API Call Type is required but is missing.";
		}

		if(empty($this->APIKey)) {
			$this->_errors[] = "API Key is is required but is missing.";
		}

		if(($this->OutputType!="json") && ($this->OutputType!="xml") && ($this->OutputType!="json")) {
			$this->_errors[] = "OutputType is required but is missing.";
		}
	}

	protected function _apiCall()
	{
		$this->_checkErrors();

		if($this->_apiCallType=="add" || $this->_apiCallType=="delete") {
			$postUrl = $this->_apiUrl."/".$this->_apiCallType."/".$this->_outputType."?key=".$this->_apiKey."&sensor=".$this->_sensor;

			if($this->_apiCallType=="add") {
				$locationArray = explode(",", $this->_location);
				$lat = trim($locationArray[0]);
				$lng = trim($locationArray[1]);

				$postData = array();
				$postData['location']['lat'] = $lat;
				$postData['location']['lng'] = $lng;
				$postData['accuracy'] = $this->_accuracy;
				$postData['name'] = $this->_name;
				$postData['types'] = explode("|", $this->_types);
				$postData['language'] = $this->_language;
			}

			if($this->_apiCallType=="delete") {
				$postData['reference'] = $this->_reference;
			}

			$result = json_decode($this->_curlCall($postUrl,json_encode($postData)));
			$result->errors = $this->_errors;
			return $result;

		}

		if($this->_apiCallType=="search") {
			$URLparams = "location=".$this->_location."&radius=".$this->_radius."&types=".$this->_types."&language=".$this->_language."&name=".$this->_name."&sensor=".$this->_sensor;
		}
	
		if($this->_apiCallType=="details") {
			$URLparams = "reference=".$this->_reference."&language=".$this->_language."&sensor=".$this->_sensor;
		}
	
		if($this->_apiCallType=="check-in") {
			$URLparams = "reference=".$this->_reference."&language=".$this->_language."&sensor=".$this->_sensor;
		}
	
		$URLToCall = $this->_apiUrl."/".$this->_apiCallType."/".$this->_outputType."?key=".$this->_apiKey."&".$URLparams;
		$result = json_decode(file_get_contents($URLToCall),true);
		$result['errors'] = $this->Errors;
	
		if($result[status]=="OK" && $this->_apiCallType=="details") {
			foreach($result[result][address_components] as $key=>$component) {
	
				if($component['types'][0]=="street_number") {
					$address_street_number = $component['short_name'];
				}
	
				if($component['types'][0]=="route") {
					$address_street_name = $component['short_name'];
				}
	
				if($component['types'][0]=="locality") {
					$address_city = $component['short_name'];
				}
	
				if($component['types'][0]=="administrative_area_level_1") {
					$address_state = $component['short_name'];
				}
	
				if($component['types'][0]=="postal_code") {
					$address_postal_code = $component['short_name'];
				}
			}

			$result['result']['address_fixed']['street_number'] = $address_street_number;
			$result['result']['address_fixed']['address_street_name'] = $address_street_name;
			$result['result']['address_fixed']['address_city'] = $address_city;
			$result['result']['address_fixed']['address_state'] = $address_state;
			$result['result']['address_fixed']['address_postal_code'] = $address_postal_code;
		}
		
		return $result;
	}

	protected function _curlCall($url,$topost)
	{
		$ch = curl_init();
		echo ($topost);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $topost);
		$body = curl_exec($ch);
		curl_close($ch);

		return $body;
	}
}
