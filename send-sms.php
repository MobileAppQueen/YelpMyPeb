<?php
header('Content-type: text/xml');
// Enter the path that the oauth library is in relation to the php file
require_once('lib/OAuth.php');

// OAuth credentials here for Yelp
$CONSUMER_KEY = "W98_vY5_TD-W0ikveKpr8Q";
$CONSUMER_SECRET = "q_jUvWQifFo8xZWfiCVogZkwKS4";
$TOKEN = "Xmjqwzd8oEFdvcWeGQqDCDdaeECPTwEN";
$TOKEN_SECRET = "1ZY2GUtnTb9bmpKDymtzHS-9Cmg";

$API_HOST = 'api.yelp.com';
$DEFAULT_TERM = 'dinner';
$DEFAULT_LOCATION = 'San Francisco, CA';
$SEARCH_LIMIT = 3;
$SEARCH_PATH = '/v2/search/';
$BUSINESS_PATH = '/v2/business/';

$inputData="";
$whatTheyWantToFind="";

getUserInfo();

function getUserInfo(){

//Get User info
$body=$_REQUEST['Body'];
$body="Find:mcdonalds store,location:mississauga on";

//Action:x,Loc:y
list($locatex, $locatey) = explode(',', $body);
list($a, $b) = explode(':', $locatex);
list($c, $d) = explode(':', $locatey);
$action=$a;
$query=$b;
$location=$d;

$inputData="Action is ". $action. " Query is ". $query. " Location is ". $location;

//Change it to if action==find, then call my funtion yellowapi later
if ($action=="find"||$action=="Find"){
	funcYelpApi($query, $location);
}
}

function funcYelpApi($need, $loc){
///The users inputs
	$whatTheyWantToFind = str_replace(' ', '+', $need);
	$whereTheyWantToFind = str_replace(' ', '+', $loc);
	query_api($whatTheyWantToFind, $whereTheyWantToFind);

}

/** 
 * Makes a request to the Yelp API and returns the response
 * 
 * @param    $host    The domain host of the API 
 * @param    $path    The path of the APi after the domain
 * @return   The JSON response from the request      
 */
function request($host, $path) {
    $unsigned_url = "http://" . $host . $path;

    // Token object built using the OAuth library
    $token = new OAuthToken($GLOBALS['TOKEN'], $GLOBALS['TOKEN_SECRET']);

    // Consumer object built using the OAuth library
    $consumer = new OAuthConsumer($GLOBALS['CONSUMER_KEY'], $GLOBALS['CONSUMER_SECRET']);

    // Yelp uses HMAC SHA1 encoding
    $signature_method = new OAuthSignatureMethod_HMAC_SHA1();

    $oauthrequest = OAuthRequest::from_consumer_and_token(
        $consumer, 
        $token, 
        'GET', 
        $unsigned_url
    );
    
    // Sign the request
    $oauthrequest->sign_request($signature_method, $consumer, $token);
    
    // Get the signed URL
    $signed_url = $oauthrequest->to_url();
    
    // Send Yelp API Call
    $ch = curl_init($signed_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $data = curl_exec($ch);
    curl_close($ch);
    
    return $data;
}


/**
 * Query the Search API by a search term and location 
 * 
 * @param    $term        The search term passed to the API 
 * @param    $location    The search location passed to the API 
 * @return   The JSON response from the request 
 */
function search($term, $location) {
    $url_params = array();
    
    $url_params['term'] = $term ?: $GLOBALS['DEFAULT_TERM'];
    $url_params['location'] = $location?: $GLOBALS['DEFAULT_LOCATION'];
    $url_params['limit'] = $GLOBALS['SEARCH_LIMIT'];
    $search_path = $GLOBALS['SEARCH_PATH'] . "?" . http_build_query($url_params);
    
    return request($GLOBALS['API_HOST'], $search_path);
}

/**
 * Query the Business API by business_id
 * 
 * @param    $business_id    The ID of the business to query
 * @return   The JSON response from the request 
 */
function get_business($business_id) {
    $business_path = $GLOBALS['BUSINESS_PATH'] . $business_id;
    
    return request($GLOBALS['API_HOST'], $business_path);
}


/**
 * Queries the API by the input values from the user 
 * 
 * @param    $term        The search term to query
 * @param    $location    The location of the business to query
 */
function query_api($term, $location) {     
    $response = json_decode(search($term, $location));
    $business_id = $response->businesses[0]->id;
    
    print sprintf(
        "%d businesses found, querying business info for the top result \"%s\"\n\n",         
        count($response->businesses),
        $business_id
    );
    
    $response = get_business($business_id);
    
    print sprintf("Result for business \"%s\" found:\n", $business_id);
    print "$response\n";
}


?>

<Response>
	<Message>
		Here's some data about the input: <?php echo $inputData; echo 'User is looking for '. $whatTheyWantToFind; ?>
	</Message>
</Response>
