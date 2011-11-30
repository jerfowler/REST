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
                'exec' => 'rest_',
		'model' => 'Model_',
                'method' => 'REST_Method_',
                'content' => 'REST_Content_'
	);
	
        public static $_methods = array(
                'GET',
                'PUT',
                'POST',
                'DELETE',
                'HEAD',
                'TRACE',
                'OPTIONS'
        );
        
        public static $_types = array(
                'text/html' => 'html',
                'application/json' => 'json',
                'application/xml' => 'xml',
                'application/rdf+xml' => 'rdf',
                'application/rss+xml' => 'rss',
                'application/atom+xml' => 'atom'
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
	 * @var  String  content HTTP Accept type
	 */
	protected $_content;

	/**
	 * @var  String  chartype HTTP Accept chartype
	 */
	protected $_chartype;        
        
        /**
	 * @var  String  language HTTP Accept language
	 */
	protected $_language;
        
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
                $this->controller($controller);
                
		$this->content(Arr::get($config, 'content', $this->accept()));
		$this->charset(Arr::get($config, 'charset', array(Kohana::$charset)));
		$this->language(Arr::get($config, 'language', array(I18n::$lang)));
	}

        /**
         * Returns the accepted content types based on Controller's interfaces
         * 
         * @return mixed
         */
	public function accept()
	{
                $accept = array();
                foreach(self::$_types as $type => $value)
                {
                        if ($this->_controller instanceof ${self::$_prefix['content'].$value})
                        {
                                $accept[] = $type;
                        }
                }
		return $accept;
	}        

	/**
	 * Return the allowed methods of the model
	 *
	 * @param   void
	 * @return  mixed
	 */
	public function allowed()
	{
		$allowed = array();
		foreach(self::$_methods as $method)
		{
			if ($this->_model instanceof ${self::$_prefix['method'].$method})
			{
				$allowed[] = $method;
			}
		}
		return $allowed;
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
	public function content(Array $types = NULL)
	{
		if ($method === NULL)
		{
			// Act as a getter
			return $this->_content;
		}

                $request = $this->request();
                
		// Act as a setter
		$this->_content = $request->headers()->preferred_accept($types);
		
		if (FALSE === $this->_content)
		{
			throw new Http_Exception_406('Supplied Accept types: :accept not supported. Supported types: :types',
				array(
					':accept' => $request->headers('Accept'),
					':types'  => implode(', ', $types)
				));
		}

		return $this;
	}

        /**
         * Set or get the charset
         * 
         * @param array $charsets
         * @return REST_Core 
         */
        public function charset(Array $charsets = NULL)
        {
                if ($charset === NULL)
                {
                        // Act as a getter
                        return $this->_charset;
                }
                
                $request = $this->request();
                
                // Act as a setter
                $this->_charset = $request->headers()->preferred_charset($charsets);
                
                if (FALSE === $this->_charset)
                {
			throw new Http_Exception_406('Supplied Accept-Charset: :accept not supported. Supported types: :types',
				array(
					':accept' => $request->headers('Accept-Charset'),
					':types'  => implode(', ', $charsets)
				));
                }
                
                return $this;
        }

        /**
         * Set or get the language
         * 
         * @param array $charsets
         * @return REST_Core 
         */
        public function language(Array $languages = NULL)
        {
                if ($charset === NULL)
                {
                        // Act as a getter
                        return $this->_language;
                }
                
                $request = $this->request();
                
                // Act as a setter
                $this->_language = $request->headers()->preferred_language($languages);
                
                if (FALSE === $this->_language)
                {
			throw new Http_Exception_406('Supplied Accept-Language: :accept not supported. Supported languages: :types',
				array(
					':accept' => $request->headers('Accept-Language'),
					':types'  => implode(', ', $languages)
				));
                }
                
                return $this;
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
         * Allows setting the method from the HTTP_X_HTTP_METHOD_OVERRIDE header
         * 
         * @param boolean $override 
         * @return  void
         */
        public function override($override = FALSE)
        {
                $request = $this->request();
                $method = ($override) 
                        ? Arr::get($_SERVER, 'HTTP_X_HTTP_METHOD_OVERRIDE', $request->method())
                        : $request->method();
                
                $this->method($method);
                
                return $this;
        }
        
	/**
	 * Execute the REST model and output the results
	 *
	 * @param   void
	 * @return  mixed
	 */
	public function execute()
	{
		if ( ! $this->_model instanceof ${self::$_prefix['method'].$this->_method})
		{
			// Send the "Method Not Allowed" response
			$this->_response->status(405)->headers('Allow', $this->allowed());
			throw new Http_Exception_405('Method :method not allowed.', array(':method' => $this->_method));
		}
		$this->_model->${self::$_prefix['exec'].$this->_method}($this);
                
                $this->request()->action(self::$_types[$this->_content]);
                $this->response()->headers('Content-Type', $this->_content);
                
                return $this;
	}

} // End Rest