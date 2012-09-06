PHP wrapper for using the Google Places API.
Based on the GPLV2 class created by [André Nosalsky](http://andrenosalsky.com/blog/2011/google-places-api-php-class/).

## BASIC USAGE ##

```php
<?php
require_once('googlePlaces.php');

$apiKey       = 'Your Google Places API Key';
$googlePlaces = new googlePlaces($apiKey);

// Set the longitude and the latitude of the location you want to search the surronds of
$latitude   = '-33.8804166';
$longitude = '151.2107662';
$googlePlaces->setLocation($latitude . ',' . $longitude);

$googlePlaces->setRadius(5000);
$results = $googlePlaces->Search();
```
