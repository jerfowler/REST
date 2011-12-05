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
                'exec'    => 'rest_',
		'model'   => 'Model_REST_',
                'method'  => 'REST_Method_',
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
                'text/html'            => 'html',
                'application/json'     => 'json',
                'application/xml'      => 'xml',
                'application/rdf+xml'  => 'rdf',
                'application/rss+xml'  => 'rss',
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
	 * Adds prefixes to common names to return the full class name
	 *
	 * @param string $type The class type
	 * @param string $name The Common name
	 * @return string
	 */
	public static function class_name($type, $name)
	{
		$prefix = isset(Rest::$_prefix[$type])
			? Rest::$_prefix[$type]
			: '';
		return strtolower($prefix.$name);
	}

	/**
	 * Removes prefixes of class names to return the common name
	 *
	 * @param string $type The class type
	 * @param object|string $name An instance of a class or the class name
	 * @return string
	 */
	public static function common_name($type, $name)
	{
		$name = is_object($name) ? get_class($name) : $name;
		$prefix = isset(Rest::$_prefix[$type])
			? Rest::$_prefix[$type]
			: '';
		return substr($name, strlen($prefix));
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
	 * @var  String  charset HTTP Accept charset
	 */
	protected $_charset;        
        
        /**
	 * @var  String  language HTTP Accept language
	 */
	protected $_language;
        
        /**
         * Loads Session and configuration options.
         * 
         * @param REST_Controller $controller
         * @param mixed $config 
         */
	public function __construct(REST_Controller $controller, $config = array())
	{
		$this->request($request = Arr::get($config, 'request', Request::initial()));
		$this->response(Arr::get($config, 'response', $request->response()));
		$this->method(Arr::get($config, 'method', $request->method()));
		$this->model(Arr::get($config, 'model', $request->action()));
                unset($config['request'], $config['response'], $config['method'], $config['model']);
                
                $this->controller($controller);
                
		$this->content(Arr::get($config, 'types', $this->accept()));
		$this->charset(Arr::get($config, 'charsets', array(Kohana::$charset)));
		$this->language(Arr::get($config, 'languages', array(I18n::$lang)));
                
                // Save the config in the object
		$this->_config = $config;
	}

        /**
         * Returns the accepted content types based on Controller's interfaces
         * 
         * @return mixed
         */
	public function accept()
	{
                $accept = array();
                foreach(Rest::$_types as $type => $value)
                {
			$content = Rest::class_name('content', $value);
                        if ($this->_controller instanceof $content)
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
		foreach(Rest::$_methods as $method)
		{
			$class = Rest::class_name('method', $method);
			if ($this->_model instanceof $class)
			{
				$allowed[] = $method;
			}
		}
		return $allowed;
	}
        
	/**
	 * Get the short-name content type of the request
	 * @return string
	 */
	public function type()
	{
		$type = $this->request()->headers('Content-Type');
		return Arr::get(Rest::$_types, $type, $type);
	}

	/**
	 * Retrieves a value from the route parameters.
	 *
	 *     $id = $request->param('id');
	 *
	 * @param   string   $key      Key of the value
	 * @param   mixed    $default  Default value if the key is not set
	 * @return  mixed
	 */
	public function param($key = NULL, $default = NULL)
	{
		return $this->request()->param($key, $default);
	}

	/**
	 * Gets HTTP POST parameters to the request.
	 *
	 * @param   mixed  $key    Key or key value pairs to set
	 * @return  mixed
	 */
	public function post($key = NULL)
	{
		return $this->request()->post($key);
	}
	
	/**
	 * Gets HTTP query string.
	 *
	 * @param   mixed   $key    Key or key value pairs to set
	 * @return  mixed
	 */
	public function query($key = NULL)
	{
		return $this->request()->query($key);
	}
	
	/**
	 * Gets HTTP body to the request or response. The body is
	 * included after the header, separated by a single empty new line.
	 *
	 * @param   string  $content Content to set to the object
	 * @param   boolean $array   Return an associative array, json only
	 * @return  mixed
	 */
	public function body($type = NULL, $array = FALSE)
	{
		$body = $this->request()->body();
		$type = ($type) ? $type : $this->type();
		switch ($type) {
		    case 'json':
			return json_decode($body, $array);
			break;
		    case 'xml':
			return new SimpleXMLElement($body);
			break;
		    default:
			return $body;
			break;
		}
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
			$class = Rest::class_name('model', $model);
			if (FALSE === class_exists($class))
			{
				// Send the "Model Not Found" response
				$this->_response->status(404);
				throw new Http_Exception_404('Resource :model not found.', array(':model' => $model));			    
			}
			$this->_model = new $class;
		}
		
		if ( ! $this->_model instanceof REST_Model)
		{
			// Send the Internal Server Error response
			$this->_response->status(500);
			throw new Http_Exception_500('Class :class does not implement REST_Model.', array(
                                ':class' => get_class($this->_model)));
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
	 * Set or get the content type
	 *
	 * @param   Array  $content  Content
	 * @return  String
	 * @return  void
	 */
	public function content(Array $types = NULL)
	{
		if ($types === NULL)
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
                if ($charsets === NULL)
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
                if ($languages === NULL)
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
         * Allows setting the method from the HTTP_X_HTTP_METHOD_OVERRIDE header
         * 
         * @param boolean $override 
         * @return  Rest
         */
        public function method_override($override = FALSE)
        {
                $request = $this->request();
                $method = ($override) 
                        ? Arr::get($_SERVER, 'HTTP_X_HTTP_METHOD_OVERRIDE', $request->method())
                        : $request->method();
                $this->method($method);
		
                return $this;
        }

        /**
         * Allows setting the content type from the Request param content_type
         * 
         * @param boolean $override 
         * @return  Rest
         */
        public function content_override($override = FALSE)
        {
		$types = $this->accept();
		
		if (FALSE === $override)
		{
			$this->content(Arr::get($this->_config, 'types', $types));
			return $this;
		}
		
		$content = $this->request()->param('content_type', FALSE);
		if (FALSE === $content) 
		{
			// No content_type param used...
			return $this;
		}
			
		$key = array_search($content, Rest::$_types);
		if (FALSE === $key)
		{
			throw new Http_Exception_406('Supplied Override Type: :accept not supported. Supported types: :types',
				array(
					':accept' => $content,
					':types'  => implode(', ', $types)
				));
		}
		
		if ( ! in_array($key, $types))
		{
			throw new Http_Exception_406('Supplied Content Type: :accept not supported. Supported types: :types',
				array(
					':accept' => $key,
					':types'  => implode(', ', $types)
				));		    
		}

		$this->_content = $key;
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
		// Delay verifying method until execute in the event of an override
		$method = Rest::class_name('method', $this->_method);
		if ( ! $this->_model instanceof $method)
		{
			// Send the "Method Not Allowed" response
			$this->_response->status(405)->headers('Allow', $this->allowed());
			throw new Http_Exception_405('Method :method not allowed.', array(':method' => $this->_method));
		}
		
		// Execute the model's method
		$exec = Rest::class_name('exec', $this->_method);
		$this->_model->$exec($this);
                
		// Set the action of the controller to the content type
                $this->request()->action(Rest::$_types[$this->_content]);
		
		// Set the Content headers
		$type = $this->_content.'; charset='.$this->_charset;
                $this->response()->headers('Content-Type', $type);
                $this->response()->headers('Content-Language', $this->_language);
		
                return $this;
	}

	public function send_created($id, $code = 201)
	{
		$request = $this->request();
		$url = array(
			$request->directory(),
			$request->controller(),
			strtolower(Rest::common_name('model', $this->_model)),
			$id
		);
		$url = URL::site(implode('/', $url), TRUE, Kohana::$index_file);	
		$this->request()->redirect($url, $code);
	}
	
	public function send_code($code = 204)
	{
		echo $this->response()
			->status($code)
			->send_headers()
			->body();

		// Stop execution
		exit;
	}
	
} // End Rest