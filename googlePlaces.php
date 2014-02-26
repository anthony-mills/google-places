<?php

class googlePlaces {

    const OK_STATUS = 'OK';

    public $_outputType = 'json'; //either json, xml or array
    public $_errors = array();
    	
    protected $_apiKey = '';
    protected $_apiUrl = 'https://maps.googleapis.com/maps/api/place';
    protected $_apiCallType = '';
    protected $_includeDetails = false;
    protected $_language = 'en';

    // REQUIRED:
    protected $_location;           // Required - This must be provided as a google.maps.LatLng object.
    protected $_query;              // Required if using textsearch
    protected $_radius = 50000;     // Required if using nearbysearch or radarsearch (50,000 meters max)
    protected $_sensor = 'false';   // Required simply True or False, is the provided $_location coming from GPS?

    protected $_types = '';              // Optional - Separate type with pipe symbol http://code.google.com/apis/maps/documentation/places/supported_types.html
    protected $_name;               // Optional
    protected $_keyword;            // Optional - "A term to be matched against all content that Google has indexed for this Place, including but not limited to name, type, and address, as well as customer reviews and other third-party content."
    protected $_rankBy = 'prominence';  // Optional - This option sorts the order of the places returned from the API, by their importance or the distance from the search point. Possible values are PROMINENCE or DISTANCE.
    protected $_reference;
    protected $_accuracy;
    protected $_pageToken;
    protected $_curloptSslVerifypeer = true; // option CURLOPT_SSL_VERIFYPEER with true value working not always

    /**
     * constructor - creates a googlePlaces object with the specified API Key
     *
     * @param $apiKey - the API Key to use
     */
    public function __construct($apiKey) {
        $this->_apiKey = $apiKey;	
    }

    // for backward compatibility
    public function search() {
        $this->_apiCallType = googlePlacesCallType::SEARCH;

        return $this->_executeAPICall();
    }

    // hits the v3 API
    public function nearbySearch() {
        $this->_apiCallType = googlePlacesCallType::NEARBY_SEARCH;
        return $this->_executeAPICall();
    }

    // hits the v3 API
    public function radarSearch() {
        $this->_apiCallType = googlePlacesCallType::RADAR_SEARCH;

        return $this->_executeAPICall();
    }

    // hits the v3 API
	public function textSearch() {
        $this->_apiCallType = googlePlacesCallType::TEXT_SEARCH;

        return $this->_executeAPICall();
    }

	public function details() {
		$this->_apiCallType = googlePlacesCallType::DETAILS_SEARCH;
		
		return $this->_executeAPICall();
	}

	public function checkIn() {
		$this->_apiCallType = googlePlacesCallType::CHECKIN;

		return $this->_executeAPICall();
	}

	public function add() {
		$this->_apiCallType = googlePlacesCallType::ADD;

		return $this->_executeAPICall();
	}

	public function delete() {
		$this->_apiCallType = googlePlacesCallType::DELETE;

		return $this->_executeAPICall();
	}

	public function repeat($pageToken) {
		$this->_apiCallType = googlePlacesCallType::REPEAT;
		$this->_pageToken = $pageToken;

		return $this->_executeAPICall();
	}

    /**
     * executeAPICall - Executes the Google Places API call specified by this class's members and returns the results as an array
     *
     * @return mixed - the array resulting from the Google Places API call specified by the members of this class
     */
    protected function _executeAPICall()
    {
        $this->_checkErrors();

        if($this->_apiCallType == googlePlacesCallType::ADD || $this->_apiCallType == googlePlacesCallType::DELETE) {
            return $this->_executeAddOrDelete();
        }

        $urlParameterString = $this->_formatParametersForURL();

        $URLToCall = $this->_apiUrl . '/' . $this->_apiCallType . '/' . $this->_outputType . '?key='.$this->_apiKey . '&' . $urlParameterString;

        $result = json_decode($this->_curlCall($URLToCall), true);

        $formattedResults = $this->_formatResults($result);

        return $formattedResults;
    }

    /**
     * _checkErrors - Checks to see if this Google Places request has all of the required fields as far as we know. In the
     * event that it doesn't, it'll populate the _errors array with an error message for each error found.
     */
	protected function _checkErrors() {
		if(empty($this->_apiCallType)) {
			$this->_errors[] = 'API Call Type is required but is missing.';
		}

		if(empty($this->_apiKey)) {
			$this->_errors[] = 'API Key is is required but is missing.';
		}

		if(($this->_outputType!='json') && ($this->outputType!='xml') && ($this->outputType!='json')) {
			$this->_errors[] = 'OutputType is required but is missing.';
		}
	}

    /**
     * _executeAddOrDelete - Executes a Google Places add or delete call based on the call type member variable. These are
     * separated from the other types because they require a POST.
     *
     * @return mixed - the Google Places API response for the given call type
     */
    protected function _executeAddOrDelete() {
        $postUrl = $this->_apiUrl . '/' . $this->_apiCallType . '/' . $this->_outputType . '?key=' . $this->_apiKey . '&sensor=' . $this->_sensor;

        if($this->_apiCallType == googlePlacesCallType::ADD) {
            $locationArray = explode(',', $this->_location);
            $lat = trim($locationArray[0]);
            $lng = trim($locationArray[1]);

            $postData = array();
            $postData['location']['lat'] = floatval($lat);
            $postData['location']['lng'] = floatval($lng);
            $postData['accuracy'] = $this->_accuracy;
            $postData['name'] = $this->_name;
            $postData['types'] = explode('|', $this->_types);
            $postData['language'] = $this->_language;
        }

        if($this->_apiCallType == googlePlacesCallType::DELETE) {
            $postData['reference'] = $this->_reference;
        }

        $result = json_decode($this->_curlCall($postUrl,json_encode($postData)));
        $result->errors = $this->_errors;
        return $result;
    }

    /**
     * _formatResults - Formats the results in such a way that they're easier to parse (especially addresses)
     *
     * @param mixed $result - the Google Places result array
     * @return mixed - the formatted Google Places result array
     */
    protected function _formatResults($result) {
        $formattedResults = array();
        $formattedResults['errors'] = $this->_errors;

        // for backward compatibility
        $resultColumnName = 'result';
        if (!isset($result[$resultColumnName])) {
            $resultColumnName = 'results';
        }

        $formattedResults['result'] = $result[$resultColumnName];

        if(isset($result['status']) && $result['status'] == self::OK_STATUS && isset($result[$resultColumnName]['address_components'])) {
            foreach($result[$resultColumnName]['address_components'] as $key => $component) {

				$address_street_number='';
				$address_street_name='';
				$address_city='';
				$address_state='';
				$address_postal_code='';

                if($component['types'][0]=='street_number') {
                    $address_street_number = $component['short_name'];
                }

                if($component['types'][0]=='route') {
                    $address_street_name = $component['short_name'];
                }

                if($component['types'][0]=='locality') {
                    $address_city = $component['short_name'];
                }

                if($component['types'][0]=='administrative_area_level_1') {
                    $address_state = $component['short_name'];
                }

                if($component['types'][0]=='postal_code') {
                    $address_postal_code = $component['short_name'];
                }
            }

            $formattedResults['result']['address_fixed']['street_number'] = $address_street_number;
            $formattedResults['result']['address_fixed']['address_street_name'] = $address_street_name;
            $formattedResults['result']['address_fixed']['address_city'] = $address_city;
            $formattedResults['result']['address_fixed']['address_state'] = $address_state;
            $formattedResults['result']['address_fixed']['address_postal_code'] = $address_postal_code;
        }

        return $formattedResults;
    }

    /**
     * _formatParametersForURL - formats the url parameters for use with a GET request depending on the call type
     *
     * @return string - the formatted parameter request string based on the call type
     */
    protected function _formatParametersForURL() {

       $parameterString = '';

        switch ($this->_apiCallType) {
            case(googlePlacesCallType::SEARCH):
                $parameterString = 'location=' . $this->_location . '&language=' . $this->_language . '&sensor=' . $this->_sensor;
                $parameterString = $this->_urlDependencies($parameterString);
                break;
            case(googlePlacesCallType::NEARBY_SEARCH):
                $parameterString = 'location=' . $this->_location . '&language=' . $this->_language . '&sensor=' . $this->_sensor;
                $parameterString = $this->_urlDependencies($parameterString);                
                break;

            case(googlePlacesCallType::RADAR_SEARCH):
                $parameterString = 'location=' . $this->_location . '&language=' . $this->_language . '&sensor=' . $this->_sensor;
                $parameterString = $this->_urlDependencies($parameterString);                
                break;

            case (googlePlacesCallType::TEXT_SEARCH):
                $parameterString = 'query=' . $this->_query . '&location=' . $this->_location . '&language=' . $this->_language . '&sensor=' . $this->_sensor;
                $parameterString = $this->_urlDependencies($parameterString);
                break;

            case(googlePlacesCallType::DETAILS_SEARCH):
                $parameterString = 'reference=' . $this->_reference . '&language=' . $this->_language . '&sensor=' . $this->_sensor;
                break;

            case(googlePlacesCallType::CHECKIN):
                $parameterString = 'reference=' . $this->_reference . '&language=' . $this->_language . '&sensor=' . $this->_sensor;
                break;

            case(googlePlacesCallType::REPEAT):
                $parameterString = 'radius='.$this->_radius . '&sensor=' . $this->_sensor . '&pagetoken=' . $this->_pageToken;
                $this->_apiCallType = 'search';
                break;
        }


        return $parameterString;
    }

    /**
    * Controls the use of incompatable parameters when constructing a URL
    *
    * i.e ranking the results by distance requires a keyword, name or types parameter to be defined but it cannot be used in conjunction with a search radius
    *
    * @param string $parameterString
    * @return string $parameterString
    */
    protected function _urlDependencies($parameterString) {
        if (($this->_rankBy == 'distance') && ((!empty($this->_types)) || (!empty($this->_name)) || (!empty($this->_keyword)))) {
            $parameterString .= '&name=' . $this->_name . '&keyword=' . $this->_keyword. '&types=' . urlencode($this->_types) . '&rankby=' . $this->_rankBy;
        } else {
            $parameterString .= '&name=' . $this->_name . '&keyword=' . $this->_keyword. '&types=' . urlencode($this->_types) . '&radius='.$this->_radius;
        }

        return $parameterString; 
    }

    /**
     * _curlCall - Executes a curl call to the specified url with the specified data to post and returns the result. If
     * the post data is empty, the call will default to a GET
     *
     * @param $url - the url to curl to
     * @param array $dataToPost - the data to post in the curl call (if any)
     * @return mixed - the response payload of the call
     */
	protected function _curlCall($url, $topost = array())
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->_curloptSslVerifypeer);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		if (!empty($topost)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $topost);
		}
		$body = curl_exec($ch);
		curl_close($ch);

		return $body;
	}



    /***********************
     * Getters and Setters *
     ***********************/

    public function setLocation($location) {
        $this->_location = $location;
    }

    public function setQuery($query) {
        $this->_query = preg_replace('/\s/', '+', $query);
    }

    public function setRadius($radius) {
        $this->_radius = $radius;
    }

    public function setTypes($types) {
        $this->_types = $types;
    }

    public function setLanguage($language) {
        $this->_language = $language;
    }

    public function setName($name) {
        $this->_name = $name;
    }

    public function setKeyword($keyword) {
        $this->_keyword = $keyword;
    }

    public function setSensor($sensor) {
        $this->_sensor = $sensor;
    }

    public function setReference($reference) {
        $this->_reference = $reference;
    }

    public function setAccuracy($accuracy) {
        $this->_accuracy = $accuracy;
    }

    public function setIncludeDetails($includeDetails) {
        $this->_includeDetails = $includeDetails;
    }

    public function setRankBy($rankBy) {
        $rankBy = strtolower($rankBy);

        if (($rankBy == 'prominence') || ($rankBy = 'distance')) {
            $this->_rankBy = $rankBy;
        }        
    }

    public function setCurloptSslVerifypeer($curloptSslVerifypeer) {
        $this->_curloptSslVerifypeer = $curloptSslVerifypeer;
    }
}

class googlePlacesCallType{
    const SEARCH = 'search';
    const NEARBY_SEARCH = 'nearbysearch';
    const RADAR_SEARCH = 'radarsearch';
    const TEXT_SEARCH = 'textsearch';
    const DETAILS_SEARCH = 'details';
    const CHECKIN = 'checkin-in';
    const ADD = 'add';
    const DELETE = 'delete';
    const REPEAT = 'repeat';
}
