<?php defined('SYSPATH') or die('No direct script access.');

class Controller_REST extends Controller 
	implements REST_Response_JSON, REST_Response_XML, REST_Response_HTML {

	protected $_rest;
	
	public function before()
	{
		parent::before();
		
		$options = array(
		    'method' => Arr::get($_SERVER, 'HTTP_X_HTTP_METHOD_OVERRIDE', $this->request->method())
		);
		$this->_rest = REST::instance($this, $options)->execute();
	}
	
	public function action_json()
	{
		$model = $this->_rest->model();
		$this->response->body(json_encode($model->values()));
	}
	
	public function action_xml()
	{
		$model = $this->_rest->model();
		$xml = new SimpleXMLElement('<root/>');
		array_walk_recursive($model->values(), array ($xml, 'addChild'));
		$this->response->body($xml->asXML());
	}
	
	public function action_html()
	{
		$model = $this->_rest->model();
		$this->response->body('<pre>'.print_r($model->values(), TRUE).'</pre>');
	}

} // End REST
