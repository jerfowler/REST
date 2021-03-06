#Kohana RESTful Web Service Library
* Author:     Jeremy Fowler
* Copyright:  (c) 2012 Jeremy Fowler
* License:    http://www.opensource.org/licenses/BSD-3-Clause

##Requires
* Kohana >= 3.2
* PHP >= 5.3

##Features
* X-HTTP-METHOD-OVERRIDE support
* Cross-Origin Resource Sharing
* ETags

##Installation

* `cd modules`
* `git clone git://github.com/jerfowler/REST.git`
* Enable the REST module in bootstrap
* Create REST extended models in classes/model/rest
* Optional: create your own custom controller_rest

##Controllers handle content type and output
Each controller must implement one or more of the REST_Content Interfaces

```php
class Controller_Template_REST extends Controller
	implements REST_Content_HTML,
		REST_Content_JSON,
		REST_Content_XML,
		REST_Content_CSV {
```
###The Rest module gets instantiated in the before method of the controller
* The model is determined by the Controller's action
* REST supports X-HTTP-METHOD-OVERRIDE by using `method_override(TRUE)`

```php
/**
 * Rest object
 * @var Rest
 */
protected $_rest;

public function before()
{
	parent::before();

	$this->_rest = REST::instance($this)
		->method_override(TRUE)
		->content_override(TRUE)
		->execute();
}
```

####Content-Type is auto-detected by the headers (as is Language & Charset)
This can be overridden by using `content_override(TRUE)` and using a special route

```php
Route::set('rest', 'rest/<action>((/<id>)(.<content_type>))')
	->defaults(array(
		'controller' => 'rest'
	));
```

###Output is handled by the various REST_Content Interface Methods

* Models pass the values generated by the HTTP method which is then retrieved by `result()` and then various other `result_x()` helper functions.
* ETags read/generated using the `etag()` method

```php
public function action_html()
{
	$values = $this->_rest->result();
	$view = View::factory('rest/html', array('values' => $values));
	$this->response->body($view);
}

public function action_json()
{
	$json = $this->_rest->etag()->result_json();
	$this->response->body($json);
}

public function action_xml()
{
	$xml = $this->_rest->etag()->result_xml();
	$this->response->body($xml->asXML());
}

public function action_csv()
{
	$csv = $this->_rest->result_csv();
	$this->response->body($csv);
}
```

##Models handle the HTTP methods
* Each model must implement one or more of the REST_Method Interfaces
* Model names are pluralized

```php
class Model_REST_Users
	implements REST_CORS,
		REST_Method_Get,
		REST_Method_Post {
```
###Each HTTP method is handled by the corresponding Interface method

```php
	/**
	 * Cross-Origin Resource Sharing
	 */
	public function rest_cors(Rest $rest)
	{
		$origin = $rest->request()->headers('Origin');
		if (in_array($origin, self::$origin))
		{
			$rest->cors(array('origin' => $origin, 'creds' => 'true'));
		}
	}

	public function rest_options(Rest $rest)
	{
		$rest->send_code(200);
	}

	public function rest_get(Rest $rest)
	{
		$data = Session::instance()->get('rest_test_data', $this->_data);
		$id = $rest->param('id');
		if ( ! empty($id))
		{
			if ( ! isset($data[$id]))
			{
				$rest->send_code(404); //Not Found
			}
			return $data[$id];

		}
		else
		{
			return $data;
		}
	}

	public function rest_put(Rest $rest)
	{
		$id = $rest->param('id');
		if ( ! empty($id))
		{
			$data = Session::instance()->get('rest_test_data', $this->_data);
			$data[$id] = $rest->body('json', TRUE);
			Session::instance()->set('rest_test_data', $data);
			return $data[$id];
		}
		else
		{
			$rest->send_code(403); //Forbidden
		}

	}

	public function rest_post(Rest $rest)
	{
		$data = Session::instance()->get('rest_test_data', $this->_data);
		$post = $rest->post();
		if (empty($post))
		{
			$post = $rest->body('json', TRUE);
		}
		$data[] = $post;
		Session::instance()->set('rest_test_data', $data);
		$rest->send_created(count($data)+1);
	}

	public function rest_delete(Rest $rest)
	{
		$id = $rest->param('id');
		$data = Session::instance()->get('rest_test_data', $this->_data);
		if (isset($data[$id]))
		{
			unset($data[$id]);
			Session::instance()->set('rest_test_data', $data);
		}
		$rest->send_code(204);
	}
```