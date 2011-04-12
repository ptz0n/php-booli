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
	 * @throws phpBooli_Missing_Client_Id_Exception
	 * @throws phpBooli_Missing_Client_Secret_Exception
	 *
	 * @access public
	 */
	function __construct($clientId, $clientSecret)
	{
		if(empty($clientId) || empty($clientSecret))
			throw new phpBooli_Missing_Client_Credentials_Exception();
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
		return json_decode($response)->booli->content;
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

		if ($this->_lastHttpResponseCode == 200)
			return $this->_lastHttpResponseBody = $body;
		else
			throw new phpBooli_Invalid_Http_Response_Code_Exception(
				$this->_lastHttpResponseBody,
				$this->_lastHttpResponseCode
			);
	}

	/**
	 * Build access token
	 *
	 * @return string
	 *
	 * @access private
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

/**
 * phpBooli missing client id exception.
 *
 * @author    Erik Pettersson <mail@ptz0n.se>
 * @copyright 2011 Erik Pettersson <mail@ptz0n.se>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://github.com/ptz0n/phpBooli
 */
class phpBooli_Missing_Client_Credentials_Exception extends Exception
{

	/**
	 * Default message.
	 *
	 * @access protected
	 *
	 * @var string
	 */
    protected $message = 'Authentication credentials missing. Please provide a valid client id and secret.';
}

/**
 * phpBooli invalid HTTP response code exception.
 *
 * @author    Erik Pettersson <mail@ptz0n.se>
 * @copyright 2011 Erik Pettersson <mail@ptz0n.se>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://github.com/ptz0n/phpBooli
 */
class phpBooli_Invalid_Http_Response_Code_Exception extends Exception
{

	/**
	 * HTTP response body.
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $httpBody;

	/**
	 * HTTP response code.
	 *
	 * @access protected
	 *
	 * @var integer
	 */
	protected $httpCode;

	/**
	 * Default message.
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $message = 'The requested URL responded with HTTP code %d. Please check your authentication credentials.';

	/**
	 * Constructor.
	 *
	 * @param string $httpBody
	 * @param integer $httpCode
	 *
	 * @return void
	 */
	function __construct($httpBody = null, $httpCode = 0)
	{
		$this->httpBody = $httpBody;
		$this->httpCode = $httpCode;
		$message = sprintf($this->message, $httpCode);
		parent::__construct($message, $code);
    }
}