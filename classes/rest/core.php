<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * RESTful web service library. 
 *
 * @package    jeremyf76/REST
 * @author     Jeremy Fowler
 * @copyright  (c) 2011 Jeremy Fowler
 * @license    http://www.opensource.org/licenses/BSD-3-Clause
 */
abstract class REST_Core {
	
	// REST instance
	protected static $_instance;

	/**
	 * Singleton pattern
	 *
	 * @return Rest
	 */
	public static function instance(REST_Model $model, $config = array())
	{
		if ( ! isset(Rest::$_instance))
		{
			// Create a new session instance
			Rest::$_instance = new Rest($model, $config);
		}

		return Rest::$_instance;
	}

	/**
	 * @var  Array  configuration options
	 */
	protected $_config;
	
	/**
	 * @var  REST_Model  model
	 */
	protected $_model;
	
	/**
	 * @var  Request  request instance
	 */
	protected $_request;

	/**
	 * @var  Kohana_Response  response instance
	 */
	protected $_response;

	/**
	 * @var  String  method HTTP method
	 */
	protected $_method;

	/**
	 * Loads Session and configuration options.
	 *
	 * @return  void
	 */
	public function __construct(REST_Model $model, $config = array())
	{
		// Save the config in the object
		$this->_config = $config;
		$this->_model = $model;
		$this->_request = Arr::get($config, 'request', Request::initial());
		$this->_response = Arr::get($config, 'response', $this->_request->response());
		$this->_method = Arr::get($config, 'override', $this->_request->method());
	}

	/**
	 * Set or get the request
	 *
	 * @param   Request  $request  Request
	 * @return  Request
	 * @return  void
	 */
	public function request(Request $request = NULL)
	{
		if ($request === NULL)
		{
			// Act as a getter
			return $this->_request;
		}

		// Act as a setter
		$this->_request = $request;

		return $this;
	}

	/**
	 * Set or get the response
	 *
	 * @param   Response  $response  Response
	 * @return  Response
	 * @return  void
	 */
	public function response(Response $response = NULL)
	{
		if ($response === NULL)
		{
			// Act as a getter
			return $this->_response;
		}

		// Act as a setter
		$this->_response = $response;

		return $this;
	}

	/**
	 * Set or get the model
	 *
	 * @param   REST_Model  $model  Model
	 * @return  REST_Model
	 * @return  void
	 */
	public function model(REST_Model $model = NULL)
	{
		if ($model === NULL)
		{
			// Act as a getter
			return $this->_model;
		}

		// Act as a setter
		$this->_model = $model;

		return $this;
	}

	/**
	 * Set or get the method
	 *
	 * @param   String  $method  Method
	 * @return  String
	 * @return  void
	 */
	public function method($method = NULL)
	{
		if ($method === NULL)
		{
			// Act as a getter
			return $this->_method;
		}

		// Act as a setter
		$this->_method = $method;

		return $this;
	}

	/**
	 * Return the allowed methods of the model
	 *
	 * @param   void
	 * @return  mixed
	 */
	public function allowed()
	{
		$methods = array('GET','PUT','POST','DELETE','HEAD','TRACE','OPTIONS');
		$allowed = array();
		foreach($methods as $method)
		{
			if ($this->_model instanceof 'Rest_Method_'.$method)
			{
				$allowed[] = $method;
			}
		}
		return $allowed;
	}

	/**
	 * Render the REST model and output the results
	 *
	 * @param   void
	 * @return  mixed
	 */
	public function render()
	{
		if ( ! $this->_model instanceof 'Rest_Method_'.$this->_method)
		{
			// Send the "Method Not Allowed" response
			$this->_response->status(405)->headers('Allow', $this->allowed());
			throw new Http_Exception_405('Method :method not allowed.', array(':method' => $this->_method));
		}
		return $this->_model->'rest_'.$this->_method($this);
	}

} // End Rest