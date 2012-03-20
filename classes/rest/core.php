<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * RESTful web service library.
 *
 * @package    jerfowler/REST
 * @author     Jeremy Fowler
 * @copyright  (c) 2012 Jeremy Fowler
 * @license    http://www.opensource.org/licenses/BSD-3-Clause
 */

abstract class REST_Core {

	// REST instance
	protected static $_instance;
	protected static $_prefix = array(
		'exec' => 'rest_',
		'model' => 'Model_REST_',
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
		'PATCH',
		'OPTIONS'
	);
	public static $_types = array(
		'text/html' => 'html',
		'application/json' => 'json',
		'application/xml' => 'xml',
		'application/rdf+xml' => 'rdf',
		'application/rss+xml' => 'rss',
		'application/atom+xml' => 'atom',
		'application/vnd.ms-excel' => 'csv'
	);
	public static $_cors = array(
		'origin' => '*',
		'methods' => null,
		'headers' => array('Origin', 'Accept', 'Accept-Language', 'Content-Type', 'X-Requested-With', 'X-CSRF-Token'),
		'expose' => null,
		'creds' => null,
		'age' => null
	);

	public static function prefix($name, $value)
	{
		if (is_null($name))
		{
			return self::$_prefix;
		}

		if (is_null($value))
		{
			return Arr::get(self::$_prefix, $name, NULL);
		}

		self::$_prefix[$name] = $value;
	}

	/**
	 * Singleton pattern
	 *
	 * @return Rest
	 */
	public static function instance(REST_Controller $controller, $config = array())
	{
		if (!isset(Rest::$_instance))
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
		$prefix = isset(Rest::$_prefix[$type]) ? Rest::$_prefix[$type] : '';
		return strtolower($prefix . $name);
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
		$prefix = isset(Rest::$_prefix[$type]) ? Rest::$_prefix[$type] : '';
		return substr($name, strlen($prefix));
	}

	public static function join($values, $glue = ', ')
	{
		return is_array($values) ? implode($glue, $values) : $values;
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
	 * @var  Mixed  result from the model's method
	 */
	protected $_result;

	/**
	 * Loads Session and configuration options.
	 *
	 * @param REST_Controller $controller
	 * @param mixed $config
	 */
	public function __construct(REST_Controller $controller, $config = array())
	{
		$default = Kohana::$config->load('rest')->as_array();
		$config = Arr::merge($default, $config);

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
	 * Execute the REST model and save the results
	 *
	 * @param   void
	 * @return  mixed
	 */
	public function execute()
	{
		// Delay verifying method until execute in the event of an override
		$method = Rest::class_name('method', $this->_method);
		if (!$this->_model instanceof $method)
		{
			// Send the "Method Not Allowed" response
			$this->_response->headers('Allow', $this->allowed());
			$this->send_code(405, array('Method :method not allowed.', array(':method' => $this->_method)));
		}

		// Check if this is a Cross-Origin Resource Sharing Model
		if ($this->_model instanceof REST_CORS)
		{
			$this->_model->rest_cors($this);
		}

		// Check if this is an Authorized Model
		if ($this->_model instanceof REST_AUTH)
		{
			if (FALSE === $this->_model->rest_auth($this))
			{
				// Unauthorized
				$this->send_code(401);
			}
		}

		// Execute the model's method, save the result
		$exec = Rest::class_name('exec', $this->_method);
		$this->_result = $this->_model->$exec($this);

		// Set the action of the controller to the content type
		$this->request()->action(Rest::$_types[$this->_content]);

		// Set the Content headers
		$type = $this->_content . '; charset=' . $this->_charset;
		$this->response()->headers('Content-Type', $type);
		$this->response()->headers('Content-Language', $this->_language);

		return $this;
	}

	/**
	 * Returns the result of the model's method
	 *
	 * @return mixed
	 */
	public function result()
	{
		return $this->_result;
	}

	/**
	 * Returns JSON encoded string of the result of the model's method
	 *
	 * @return String
	 */
	public function result_json()
	{
		return json_encode($this->_result);
	}

	/**
	 * Generates SimpleXML object from the result of the model's method
	 *
	 * @param string $name Optional name of the root node, defaults to model's name
	 * @return SimpleXMLElement
	 */
	public function result_xml($name = NULL)
	{
		$values = $this->result();
		if(is_null($name))
		{
			$model = $this->model();
			$name = strtolower(Rest::common_name('model', $model));
		}

		$walk = function(Array $vars, $xml, $node) use (&$walk)
		{
			foreach ($vars as $name => $value)
			{
				if (is_array($value))
				{
					$name = is_int($name) ? $node : $name;
					$sub = $xml->addChild($name);
					$walk($value, $sub, Inflector::singular($name));
				}
				else
				{
					$xml->addChild($name, htmlentities($value, ENT_QUOTES));
				}
			}
		};

		// Check for associative array
		if (array_keys($values) !== range(0, count($values) - 1))
		{
			$xml = new SimpleXMLElement('<' . Inflector::singular($name) . '/>');
		}
		else
		{
			$xml = new SimpleXMLElement('<' . $name . '/>');
		}

		$walk($values, $xml, Inflector::singular($name));
		return $xml;
	}

	/**
	 * Generates MS Excel Formated CSV string
	 *
	 * @param string $filename Optional filename of the CSV, defaults to model's name
	 * @return string
	 */
	public function result_csv($filename = NULL)
	{
		$model = $this->model();
		if (is_null($filename))
		{
			$filename = strtolower(Rest::common_name('model', $model));
		}
		$this->response()->headers('Content-disposition', 'filename='.$filename.'.csv');

		$csv = '';
		$values = $this->result();
		if (empty($values)) return $csv;

		$titles = function(Array $vars, $node) use (&$titles)
		{
			$result = array();
			foreach ($vars as $name => $value)
			{
				if (is_array($value))
				{
					$name = is_int($name) ? $node.'_'.$name : $name;
					$result[] = $titles($value, $name);
				}
				else
				{
					$result[] = empty($node) ? $name : $node.'.'.$name;
				}
			}
			return implode('","', $result);
		};

		$walk = function(Array $vars) use (&$walk)
		{
			$result = array();
			foreach ($vars as $name => $value)
			{
				if (is_array($value))
				{
					$result[] = $walk($value);
				}
				else
				{
					$result[] = str_replace('"', '""', $value);
				}
			}
			return implode('","', $result);
		};

		// Check for associative array
		if (array_keys($values) !== range(0, count($values) - 1))
		{
			$csv  = '"'.$titles($values, '')."\"\n";
			$csv .= '"'.$walk($values)."\"\n";
		}
		else
		{
			$csv  = '"'.$titles($values[0], '')."\"\n";
			foreach ($values as $row)
			{
				$csv .= '"'.$walk($row)."\"\n";
			}
		}
		return $csv;
	}

	/**
	 * Checks ETag, sends 304 on match, generates ETag header
	 *
	 * @param string $hash The hash used to generate the ETag, defaults to sha1
	 * @return REST_Core
	 */
	public function etag($hash = 'sha1')
	{
		$match = $this->request()->headers('If-None-Match');
		$etag = $hash($this->result_json());
		if ($match === $etag)
		{
			$this->send_code(304);
		}
		else
		{
			$this->response()->headers('ETag', $etag);
		}

		return $this;
	}

	/**
	 * Returns the accepted content types based on Controller's interfaces
	 *
	 * @return mixed
	 */
	public function accept()
	{
		$accept = array();
		foreach (Rest::$_types as $type => $value)
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
		foreach (Rest::$_methods as $method)
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
	public function query($key = NULL, $array = TRUE)
	{
		if (is_null($key))
		{
			$query = $this->request()->query($key);
			foreach ($query as $name => $value)
			{
				if ($value == '')
				{
					return json_decode($name, $array);
				}
			}
			return $query;
		}
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
		switch ($type)
		{
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
				$this->send_code(404, array('Resource ":model" not found.', array(':model' => $model)));
			}
			$this->_model = new $class;
		}

		if (!$this->_model instanceof REST_Model)
		{
			// Send the Internal Server Error response
			$this->send_code(500, array('Class :class does not implement REST_Model.', array(':class' => get_class($this->_model))));
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
			$this->send_code(406, array('Supplied Accept types: :accept not supported. Supported types: :types',
				array(
					':accept' => $request->headers('Accept'),
					':types' => implode(', ', $types)
				)));
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
			$this->send_code(406, array('Supplied Accept-Charset: :accept not supported. Supported types: :types',
				array(
					':accept' => $request->headers('Accept-Charset'),
					':types' => implode(', ', $charsets)
				)));
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
			$this->send_code(406, array('Supplied Accept-Language: :accept not supported. Supported languages: :types',
				array(
					':accept' => $request->headers('Accept-Language'),
					':types' => implode(', ', $languages)
				)));
		}

		return $this;
	}

	/**
	 * Allows setting the method from the X-HTTP-METHOD-OVERRIDE header
	 *
	 * @param boolean $override
	 * @return  Rest
	 */
	public function method_override($override = FALSE)
	{
		$request = $this->request();
		$method = $request->headers('X-HTTP-METHOD-OVERRIDE');
		$method = (isset($method) AND $override) ? $method : $request->method();
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
			$this->send_code(406, array('Supplied Override Type: :accept not supported. Supported types: :types',
				array(
					':accept' => $content,
					':types' => implode(', ', $types)
				)));
		}

		if (!in_array($key, $types))
		{
			$this->send_code(406, array('Supplied Content Type: :accept not supported. Supported types: :types',
				array(
					':accept' => $key,
					':types' => implode(', ', $types)
				)));
		}

		$this->_content = $key;
		return $this;
	}

	/**
	 * Cross-Origin Resource Sharing Helper
	 *
	 * @param array $values
	 * @return Rest
	 */
	public function cors(Array $values = array())
	{
		$cors = self::$_cors;
		$cors['methods'] = $this->allowed();
		$cors = Arr::merge($cors, $values);

		$response = $this->response();

		if (isset($cors['origin']))
		{
			$response->headers('Access-Control-Allow-Origin', self::join($cors['origin']));
		}

		if (isset($cors['methods']))
		{
			$response->headers('Access-Control-Allow-Methods', self::join($cors['methods']));
		}

		if (isset($cors['headers']))
		{
			$response->headers('Access-Control-Allow-Headers', self::join($cors['headers']));
		}

		if (isset($cors['expose']))
		{
			$response->headers('Access-Control-Expose-Headers', self::join($cors['expose']));
		}

		if (isset($cors['creds']))
		{
			$response->headers('Access-Control-Allow-Credentials', $cors['creds']);
		}

		if (isset($cors['age']))
		{
			$response->headers('Access-Control-Max-Age', $cors['age']);
		}

		return $this;
	}

	/**
	 * Sends the created response (POST)
	 *
	 * @param type $id
	 * @param type $code
	 */
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

	/**
	 * Sends the response code and exits the application
	 *
	 * @param type $code
	 * @param mixed $body
	 */
	public function send_code($code = 204, $body = NULL)
	{
		// Echo response and exit if we aren't using exceptions
		if (FALSE === Arr::get($this->_config, 'exceptions', FALSE))
		{
			if (is_array($body))
			{
				list($str, $pairs) = $body;
				$body = strtr($str, $pairs);
			}
			echo $this->response()
				->status($code)
				->send_headers()
				->body($body);

			// Stop execution
			exit;
		}
		else
		{
			// See if special exception class exists
			$class = 'Http_Exception_'.$code;
			if (class_exists($class))
			{
				if (is_array($body))
				{
					list($str, $pairs) = $body;
					throw new $class($str, $pairs);
				}
				else
				{
					throw new $class($body);
				}
			}
			else
			{
				if (is_array($body))
				{
					list($str, $pairs) = $body;
					throw new HTTP_Exception($str, $pairs, $code);
				}
				else
				{
					throw new HTTP_Exception($body, NULL, $code);
				}
			}
		}
	}
}
// End Rest