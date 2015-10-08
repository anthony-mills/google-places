PHP wrapper for using the Google Places API.
Based on the GPLV2 class created by [André Nosalsky](http://andrenosalsky.com/blog/2011/google-places-api-php-class/).

## BASIC USAGE ##

```php
<?php
require_once('googlePlaces.php');

$apiKey       = 'Your Google Places API Key';
$googlePlaces = new googlePlaces($apiKey);

// Set the longitude and the latitude of the location you want to search near for places
$latitude   = '-33.8804166';
$longitude = '151.2107662';
$googlePlaces->setLocation($latitude . ',' . $longitude);

$googlePlaces->setRadius(5000);
$results = $googlePlaces->search(); //
```

A search query can be run again for a fresh set of results using the "paging" functionality that was recently added to the API.

To use simply perform a place search as per normal, and then call the repeat method afterward with the 'next_page_token' element returned by the first search eg. 

```php
$firstSearch = $googlePlaces->Search();

if (!empty($firstSearch['next_page_token'])) {
	$secondSearch = $googlePlaces->repeat($firstSearch['next_page_token']);
}
```
The repeat function can be used twice for each search function allowing up to 60 individual results for each search request. 

### Proxy ###
When you use deployment server that changes IP address each time you run a new build, you may want to funnel your request through only one IP address. Therefore, you can allow only that single IP in Google Developers Console `server key`. For that purpose you should setup proxy to route your Google Maps API requests through that.

When your proxy is set up, you can use it with googlePlaces.

```php
$proxy = [];
$proxy["host"] = "your host name";
$proxy["port"] = 8080;
$proxy["username"] = "your username"; //optional with password
$proxy["password"] = "your password";
$apiKey       = 'Your Google Places API Key';
$googlePlaces = new googlePlaces($apiKey, $proxy);

```