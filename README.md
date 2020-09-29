# How to connect to value from Custom Endpoint to show values from website

## 1. Using `curl` 

```php
	$request_url = 'http://{address_to_your_website}/wp-json/website_info/v1/website-details/';
	$credentials = array();
	$credentials = array('username: {username}', 'password: {password}');

	$curl_handle = curl_init( );
	curl_setopt( $curl_handle, CURLOPT_URL, $request_url );
	curl_setopt( $curl_handle, CURLOPT_CONNECTTIMEOUT, 0 );
	curl_setopt( $curl_handle, CURLOPT_TIMEOUT, 15 );
	curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, $credentials  );
	curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, TRUE );

	$JsonResponse = curl_exec( $curl_handle );
	$http_code = curl_getinfo( $curl_handle );

	if ( 200 == $http_code[ 'http_code' ] ) {
		echo  '<h2>'.get_bloginfo() .'</h2>' . '<br>';
		echo $JsonResponse;
	} else {
		echo 'ERROR: <pre>', var_export( $JsonResponse, true ), "</pre>\n";
	}
```

Replace:
```{address_to_your_website}``` i.e ```website.com```.
```{username}``` i.e ```'username123'``` must be string.
```{password}``` i.e ```'password123'``` must be string.

## 2. Using Postman directly to API address.


![alt text](https://github.com/Fichtner21/Web-Info-API-with-Auth/blob/master/Basic_postman_API.PNG?raw=true)