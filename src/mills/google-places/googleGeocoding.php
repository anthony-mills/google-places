<?php
namespace Mills\GooglePlaces;

class googleGeocoding {

    const OK_STATUS = 'OK';

    const COMPONENTS_FIELD_NAME = 'components';
    const LAT_LNG_FIELD_NAME = 'latlng';
    const SENSOR_FIELD_NAME = 'sensor';
    const ADDRESS_FIELD_NAME = 'address';
    const BOUNDS_FIELD_NAME = 'bounds';
    const LANGUAGE_FIELD_NAME = 'language';
    const REGION_FIELD_NAME = 'region';

    public $_outputType = 'json'; //either json, xml or array
    public $_errors = array();

    protected $_apiKey = '';
    protected $_apiUrl = 'https://maps.googleapis.com/maps/api/geocode';

    // REQUIRED
    protected $_address;            // Required if no latlng or components - The address that you want to geocode.
    protected $_latlng;             // Required if no address or components - The textual latitude/longitude value for which you wish to obtain the closest, human-readable address. See Reverse Geocoding for more information.
    protected $_components;         // Required if no address or latlng - A component filter for which you wish to obtain a geocode. See Component Filtering for more information. The components filter will also be accepted as an optional parameter if an address is provided.
    protected $_sensor = 'false';   // Indicates whether or not the geocoding request comes from a device with a location sensor. This value must be either true or false.

    // OPTIONAL
    protected $_bounds;             // The bounding box of the viewport within which to bias geocode results more prominently. This parameter will only influence, not fully restrict, results from the geocoder.
    protected $_language = 'en';    // The language in which to return results. See the list of supported domain languages. Note that we often update supported languages so this list may not be exhaustive. If language is not supplied, the geocoder will attempt to use the native language of the domain from which the request is sent wherever possible.
    protected $_region;             // The region code, specified as a ccTLD ("top-level domain") two-character value. This parameter will only influence, not fully restrict, results from the geocoder. (For more information see Region Biasing below.)

    protected $_curloptSslVerifypeer = true; // option CURLOPT_SSL_VERIFYPEER with true value working not always
	protected $_curlReferer;

    /**
     * constructor - creates a googleGeocoding object with the specified API Key
     *
     * @param $apiKey - the API Key to use
     */
    public function __construct($apiKey) {
        $this->_apiKey = $apiKey;
    }

    /**
     * executeAPICall - Executes the Google Geocode API call specified by this class's members and returns the results as an array
     *
     * @return mixed - the array resulting from the Google Geocode API call specified by the members of this class
     */
    public function executeAPICall() {

        $this->_checkErrors();

        $urlParameters = $this->_formatParametersForURL();

        $URLToCall = $this->_apiUrl . '/' . $this->_outputType . '?'. $urlParameters;

        $result = json_decode($this->_curlCall($URLToCall), true);

        $formattedResults = $this->_formatResults($result);

        return $formattedResults;
    }

    /**
     * _checkErrors - Checks to see if this google Geocoding request has all of the required fields as far as we know. In the
     * event that it doesn't, it'll populate the _errors array with an error message for each error found.
     */
    protected function _checkErrors() {

        if (empty($this->_apiKey)) {
            $this->_errors[] = 'API Key is is required but is missing.';
        }

        if (($this->_outputType != 'json') && ($this->outputType != 'xml')) {
            $this->_errors[] = 'OutputType is required but is missing.';
        }
    }

    /**
     * _formatParametersForURL - Formats the parameters of the Google Geocoding call for a GET request
     *
     * @return string - the parameters in URL string form
     */
    protected function _formatParametersForURL() {

        return  self::ADDRESS_FIELD_NAME.'='.$this->_address .
            '&'.self::LAT_LNG_FIELD_NAME.'='.$this->_latlng .
            '&'.self::COMPONENTS_FIELD_NAME.'='.$this->_components .
            '&'.self::LANGUAGE_FIELD_NAME.'='.$this->_language .
            '&'.self::BOUNDS_FIELD_NAME.'='.$this->_bounds .
            '&'.self::REGION_FIELD_NAME.'='.$this->_region .
            '&'.self::SENSOR_FIELD_NAME.'='.$this->_sensor;
    }

    /**
     * _formatResults - Formats the results in such a way that they're easier to parse (especially addresses)
     *
     * @param mixed $result - the Google Geocode result array
     * @return mixed - the formatted Google Geocode result array
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
     * _curlCall - Executes a curl call to the specified url with the specified data to post and returns the result. If
     * the post data is empty, the call will default to a GET
     *
     * @param $url - the url to curl to
     * @param array $dataToPost - the data to post in the curl call (if any)
     * @return mixed - the response payload of the call
     */
    protected function _curlCall($url, $dataToPost = array()) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->_curloptSslVerifypeer);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		if ($this->_curlReferer) curl_setopt($ch, CURLOPT_REFERER, $this->_curlReferer);

        if (!empty($dataToPost)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataToPost);
        }

        $body = curl_exec($ch);
        curl_close($ch);

        return $body;
    }



    /***********************
     * Getters and Setters *
     ***********************/

    public function setCurlReferer($referer) {
        $this->_curlReferer = $referer;
    }

    public function setAddress($address) {
        $this->_address = urlencode($address);
    }

    public function setLatlng($latlng) {
        $this->_latlng = $latlng;
    }

    public function setComponents($components) {
        $this->_components = $components;
    }

    public function setSensor($sensor) {
        $this->_sensor = $sensor;
    }

    public function setBounds($bounds) {
        $this->_bounds = $bounds;
    }

    public function setLanguage($language) {
        $this->_language = $language;
    }

    public function setRegion($region) {
        $this->_region = $region;
    }

    public function setCurloptSslVerifypeer($curloptSslVerifypeer) {
        $this->_curloptSslVerifypeer = $curloptSslVerifypeer;
    }
}
