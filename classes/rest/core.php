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

	public static $_prefix = array(
		'model' => 'Model_'
	);
	
	/**
	 * Singleton pattern
	 *
	 * @return Rest
	 */
	public static function instance(REST_Controller $controller, $config = array())
	{
		if ( ! isset(Rest::$_instance))
		{
			// Create a new session instance
			Rest::$_instance = new Rest($controller, $config);
		}

		return Rest::$_instance;
	}

	/**
	 * @var  Array  configuration options
	 */
	protected $_config;
	
	/**
	 * @var  REST_Controller  model
	 */
	protected $_controller;
	
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
	 * @var  String  content HTTP content type
	 */
	protected $_content;

	/**
	 * Loads Session and configuration options.
	 *
	 * @return  void
	 */
	public function __construct(REST_Controller $controller, $config = array())
	{
		// Save the config in the object
		$this->_config = $config;
		$this->request($request = Arr::get($config, 'request', Request::initial()));
		$this->response(Arr::get($config, 'response', $request->response()));
		$this->method(Arr::get($config, 'method', $request->method()));
		$this->model(Arr::get($config, 'model', $request->action()));
		$this->content(Arr::get($config, 'content', array(Kohana::$content_type)));
		$this->charset(Arr::get($config, 'charset', array(Kohana::$charset)));
		$this->language(Arr::get($config, 'language', array(I18n::$lang)));
		$this->controller($controller);
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
	 * Set or get the model
	 *
	 * @param   mixed  $model  Model
	 * @return  REST_Model
	 * @return  void
	 */
	public function model($model = NULL)
	{
		if ($model === NULL)
		{
			// Act as a getter
			return $this->_model;
		}

		// Act as a setter
		if (is_object($model))
		{
			$this->_model = $model;
		}
		else
		{
			try
			{
				$this->_model = new ${$_prefix['model'].$model};
			}
			catch(Exception $e)
			{
				// Send the "Model Not Found" response
				$this->_response->status(404);
				throw new Http_Exception_404('Model :model not found.', array(':model' => $model));			    
			}
		}
		
		if ( ! $this->_model instanceof REST_Model)
		{
			// Send the Internal Server Error response
			$this->_response->status(500);
			throw new Http_Exception_500('Model :model does not implement REST_Model.', array(':model' => $model));
		}
		
		return $this;
	}

	/**
	 * Set or get the content type
	 *
	 * @param   Array  $content  Content
	 * @return  String
	 * @return  void
	 */
	public function content(Array $content = NULL)
	{
		if ($method === NULL)
		{
			// Act as a getter
			return $this->_content;
		}

		// Act as a setter
		$this->_content = $this->request()->headers()->preferred_accept($content);
		
		if (FALSE === $this->_content)
		{
			throw new Http_Exception_406('Supplied accept mimes: :accept not supported. Supported mimes: :mimes',
				array(
					':accept' => (string) $request_header['accept'],
					':mimes'  => implode(', ', $content)
				));
		}

		return $this;
	}

	public function accept()
	{
		return array();
	}
	
	/**
	 * Set or get the controller
	 *
	 * @param   REST_Controller  $controller  Controller
	 * @return  REST_Controller
	 * @return  void
	 */
	public function controller(REST_Controller $controller = NULL)
	{
		if ($controller === NULL)
		{
			// Act as a getter
			return $this->_controller;
		}

		// Act as a setter
		$this->_controller = $controller;

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
			if ($this->_model instanceof ${'Rest_Method_'.$method})
			{
				$allowed[] = $method;
			}
		}
		return $allowed;
	}
	
	/**
	 * Execute the REST model and output the results
	 *
	 * @param   void
	 * @return  mixed
	 */
	public function execute()
	{
		if ( ! $this->_model instanceof ${'Rest_Method_'.$this->_method})
		{
			// Send the "Method Not Allowed" response
			$this->_response->status(405)->headers('Allow', $this->allowed());
			throw new Http_Exception_405('Method :method not allowed.', array(':method' => $this->_method));
		}
		return $this->_model->${'rest_'.$this->_method}($this);
	}

} // End Rest