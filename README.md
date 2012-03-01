#Kohana RESTful Web Service Library.

* Author:     Jeremy Fowler
* Copyright:  (c) 2012 Jeremy Fowler
* License:    http://www.opensource.org/licenses/BSD-3-Clause

##Features
* Cross-Origin Resource Sharing
* ETags enabled

##Installation

* cd modules
* git clone git://github.com/jerfowler/REST.git
* Enable the REST module in bootstrap
* create REST extended models in classes/model/rest
* Optional: create your own custom controller_rest

##Quick Start

###Controllers handle content type and output
Each controller must implement one or more of the REST_Content Interfaces

```
class Controller_Template_REST extends Controller
	implements REST_Content_HTML,
		REST_Content_JSON,
		REST_Content_XML,
		REST_Content_CSV {
```

###Models handle the HTTP methods
Each model must implement one or more of the REST_Method Interfaces

```
class Model_REST_Users
	implements REST_CORS,
		REST_Method_Get,
		REST_Method_Post {
```

###The Rest module gets instantiated in the before method of the controller
* REST supports HTTP_X_HTTP_METHOD_OVERRIDE by using `method_override(TRUE)`

```
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
#### Content-Type is auto-detected by the headers
This can be overridden by using `content_override(TRUE)` and using a special route

```
Route::set('rest', 'rest/<action>((/<id>)(.<content_type>))')
	->defaults(array(
		'controller' => 'rest'
	));
```
