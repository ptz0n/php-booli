<?php
/**
 * Booli API wrapper
 *
 * @author    Erik Pettersson <mail@ptz0n.se>
 * @copyright 2011 Erik Pettersson <mail@ptz0n.se>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://github.com/ptz0n/phpBooli
 */
class phpBooli
{

    /**
     * Auth client id
     *
     * @var string
     *
     * @access private
     */
	private $_clientId;

    /**
     * Auth client secret
     *
     * @var string
     *
     * @access private
     */
	private $_clientSecret;

    /**
     * API domain
     *
     * @var string
     *
     * @access private
     */
	private static $_domain = 'api.booli.se';

    /**
     * Request path
     *
     * @var string
     *
     * @access private
     */
	private $_path = '';

    /**
     * Default cURL options
     *
     * @var array
     *
     * @access private
     * @static
     */
     private static $_curlDefaultOptions = array(
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_USERAGENT => 'phpBooli'
     );

 	/**
  	* cURL options
  	*
  	* @var array
  	*
  	* @access private
  	*/
 	private $_curlOptions;

    /**
     * HTTP response body from the last request
     *
     * @var string
     *
     * @access private
     */
    private $_lastHttpResponseBody;

    /**
     * HTTP response code from the last request
     *
     * @var integer
     *
     * @access private
     */
    private $_lastHttpResponseCode;

    /**
     * HTTP response headers from last request
     *
     * @var array
     *
     * @access private
     */
    private $_lastHttpResponseHeaders;

    /**
     * Class constructor
     *
     * @param string  $clientId     Auth client id
     * @param string  $clientSecret Auth client secret
     *
     * @return void
     *
     * @access public
     */
	function __construct($clientId, $clientSecret)
	{
		if($clientId == '' || $clientSecret == '')
			throw new Exception('Authentication credentials missing. Please provice a valid client id and secret.');
		$this->_clientId = $clientId;
		$this->_clientSecret = $clientSecret;
		$this->_curlOptions = self::$_curlDefaultOptions;
	}
	
	/**
	 * Listing
	 * 
	 * @param string	$area
	 * @param array 	$filter
	 * @param int		$offset
	 * @param int 		$count
	 * 
	 * @return array
	 * 
     * @access public
	 */
	public function listing($area = '', $filter = array(), $offset = 0, $count = 100)
	{
		$return = false;
		$filter['offset']	= $offset;
		$filter['count']	= $count;
		if($area != '') $this->_path = $area.'/';
		$url = self::_buildUrl($filter);
		$response = self::_request($url);
		if($this->_lastHttpResponseCode == 200)
			return json_decode($response)->booli->content;
		else
			throw new Exception('Response code '.$this->_lastHttpResponseCode.' given from Booli API. Check your client id and secret.');
	}

    /**
     * Performs the actual HTTP request using cURL
     *
     * @param string $url Absolute URL to request
     *
     * @return void
     *
     * @access protected
     */
	protected function _request($url)
	{
		$ch = curl_init($url);
		curl_setopt_array($ch, $this->_curlOptions);
		$body = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		$this->_lastHttpResponseCode = $info['http_code'];
		return $this->_lastHttpResponseBody = $body;
	}

    /**
     * Build access token
     *
     * @return string
     *
     * @access private
     * @see Soundcloud::_getAccessToken()
     */
	private function _accessToken()
	{
		return sha1(
			$this->_clientId.
			date('c').
			$this->_clientSecret.
			rand(0, PHP_INT_MAX));
	}

	/**
	 * Construct a URL
	 *
	 * @param array   $data	Query string parameters
	 *
	 * @return string $url
	 *
	 * @access protected
	 */
	protected function _buildUrl($data)
	{
		$accessToken		= self::_accessToken();
		$data['key']		= $this->_clientId;
		$data['format']		= 'json';
		$data[$accessToken]	= false;
		return 'http://'.
			self::$_domain.
			'/listing/'.
			$this->_path.
			'?'.
			http_build_query($data);
	}
}