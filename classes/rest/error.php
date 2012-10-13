<?php
/**
 * Helps for getting your RESTful Web Service in one standard with error handling
 * Feel free to extend it and have the members you need
 * 
 * @package    jerfowler/REST
 * @author 	   Gal Schlezinger <gal@spitfire.co.il>
 * @copyright  (c) 2012 Gal Schlezinger
 * @license    http://www.opensource.org/licenses/BSD-3-Clause
 */
class REST_Error {
	/**
	 * Holds the name of the error
	 * @var string
	 */
	var $_name;

	/**
	 * Holds the message of the error
	 * @var string
	 */
	var $_msg;

	/**
	 * Creates a new instance of the RESTful error
	 * @param string $name
	 * @param string $msg
	 */
	public function __construct($name = "", $msg = "") {
		$this->_name = $name;
		$this->_msg = $msg;
	}

	/**
	 * JSONify the error
	 * @return string
	 */
	public function toJSON() {
		$jsonArray = array(
				"name" => $this->_name,
				"msg" => $this->_msg
		);

		return json_encode($jsonArray);
	}

	/**
	 * Converting to string = JSONing it
	 * @return string
	 */
	public function __ToString() {
		return $this->toJSON();
	}
}
?>