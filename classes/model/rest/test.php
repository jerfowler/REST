<?php defined('SYSPATH') or die('No direct script access.');

class Model_REST_Test implements REST_Method_Basic {

	protected $_data = array(
		1 => array('one', 'two', 'three'),
		2 => array('four', 'five', 'six'),
		3 => array('seven', 'eight', 'nine')
	);

	protected $_values;
	
	/**
	 *
	 * @param Rest $rest 
	 */
	public function rest_get(Rest $rest) 
	{
		$id = $rest->param('id');
		if ( ! empty($id))
		{
			if ( ! isset($this->_data[$id]))
			{
				throw new Http_Exception_404('Resource not found, ID: :id', array(':id' => $id));
			}
			$this->_values = $this->_data[$id];
			
		}
		else
		{
			$this->_values = $this->_data;
		}
	}

	/**
	 *
	 * @param Rest $rest 
	 */
	public function rest_put(Rest $rest) 
	{
		// TODO
		$rest->send_code(403); //Forbidden
	}

	/**
	 *
	 * @param Rest $rest 
	 */
	public function rest_post(Rest $rest) 
	{
		$id = $rest->param('id');
		if ( ! empty($id))
		{
			// TODO
			$rest->send_code(403); //Forbidden		    
		}
		else
		{
			$this->_data[] = $rest->post();
			$rest->send_created(count($this->_data)+1);		    
		}
	}

	/**
	 *
	 * @param Rest $rest 
	 */
	public function rest_delete(Rest $rest) 
	{
		$id = $rest->param('id');
		if (isset($this->_data[$id]))
		{
			unset($this->_data[$id]);
		}
		$rest->send_code(204);
	}

	public function values()
	{
		return $this->_values;
	}
}