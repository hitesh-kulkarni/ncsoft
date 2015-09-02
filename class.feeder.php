<?php
/**
* Class for fetching RSS feeds
*
* This class fetches RSS feeds and converts them 
* into JSON/JSONP responses for HTTP calls.
*
* This API can be called using URL (For JSONP)
* http://localhost:8888/test/ncsoft/?callback=&feed_url=http://www.npr.org/rss/rss.php?id=1003 
* OR (For JSON)
* http://localhost:8888/test/ncsoft/?feed_url=http://www.npr.org/rss/rss.php?id=1003 
*
*/

class Feeder {
	
	/**
	* @var string $feed_url 	URL for the XML feed
	* @var array $response 		Response for the API request
	*/
	public $feed_url, $response;

	/**
	* @var string $callback 	   Callback incase of JSONP request
	* @var string $feed_response   XML of the RSS feed
	*/
	private $callback, $feed_response;

	/**
	* Instantiates the Object of type Feeder
	*
	* @param String $feed_url; URL for the RSS feed
	* @param String $callback; Callback for JSONP requests
	* @return Feeder
	* @throws None
	* @access public
	*/
	public function __construct($feed_url, $callback="") {
		$this->response = array("response" => array(), "error" => array());
		$this->feed_url = filter_var($feed_url, FILTER_VALIDATE_URL);
		$this->callback = $callback;
		if(empty($this->feed_url)) {
			$this->response['error'][] = "Invalid Feed URL";
			$this->respond(400);
		}
		$this->fetch_url($this->feed_url);
	}

	/**
	* Sends out appropriate HTTP response based on status code
	*
	* @param Number $http_code; Type of HTTP response
	* @throws None
	* @access private
	*/
	private function respond($http_code) {
		$codes = array(    
			200 => 'OK',
			400 => 'Bad Request', 
			401 => 'Unauthorized',
			403 => 'Forbidden',
			404 => 'Not Found'
		);
		$json = json_encode($this->response);
		$headers = 'HTTP/1.1 '.$http_code.' '.$codes[$http_code];
		header('Access-Control-Allow-Origin: *');
		header('Content-Type: application/json');
		header($headers);
		if(!empty($this->callback)) {
			echo "{$this->callback}($json)";
		}
		else {
			echo $json;
		}	
		exit(1);	
	}

	/**
	* Fetches XML for RSS feed requested.
	*
	* @param String $url; URL for the RSS feed.
	* @throws None
	* @access private
	*/
	private function fetch_url($url) {
		$curl_handle = curl_init($url);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
		$this->feed_response = curl_exec($curl_handle);
		$content_type = curl_getinfo($curl_handle, CURLINFO_CONTENT_TYPE);
		$http_status_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		curl_close($curl_handle);
		if(empty($this->feed_response) || $http_status_code != 200) {
			$this->response['error'][] = "Feed URL not responding";
			$this->respond(400);
		}
		if(stristr($content_type, "text/xml") === FALSE) { 
			$this->response['error'][] = "Not a RSS URL ".$content_type;
			$this->respond(400);	
		}
		$this->parse_response();
	}

	/**
	* Parses Feed XML and builds response
	* @throws None
	* @access private
	*/
	private function parse_response() {
		$xml_object = simplexml_load_string($this->feed_response, 'SimpleXMLElement', LIBXML_NOCDATA);

		$xml_array = json_decode(json_encode($xml_object), TRUE);

		$this->response['response']['language'] = $xml_array['channel']['language'] ? $xml_array['channel']['language'] : "";

		foreach($xml_array['channel']['item'] as $entry) {
			$curr_entry = array();
			$curr_entry['date'] = date("F d, Y", strtotime($entry['pubDate']));
			$curr_entry['title'] = $entry['title'];
			$curr_entry['excerpt'] = $entry['description'];
			$this->response['response']['item'][] = $curr_entry;
		}
		$this->respond(200);
	}

}
?>