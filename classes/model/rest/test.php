<?php defined('SYSPATH') or die('No direct script access.');

class Model_REST_Test implements REST_CORS, REST_AUTH, REST_Method_Basic {

	protected static $origin = array(
		'http://example.com:3001',
		'http://www.example.com',
		'http://test.example.com'
	);

	/**
	 * Sample data
	 *
	 * @var mixed
	 */
	protected $_data = array(
		array('id' => 1, 'title' => 'one', 'author' => 'Jeremy', 'content' => 'Hi'),
		array('id' => 2, 'title' => 'two', 'author' => 'Tara', 'content' => 'Hello'),
		array('id' => 3, 'title' => 'three', 'author' => 'Isaac', 'content' => 'Hey'),
		array('id' => 4, 'title' => 'four', 'author' => 'Zander', 'content' => 'Yo'),
		array('id' => 5, 'title' => 'five', 'author' => 'Bryan', 'content' => 'Holla'),
		array('id' => 6, 'title' => 'six', 'author' => 'Jon', 'content' => 'Ciao'),
		array('id' => 7, 'title' => 'seven', 'author' => 'Leah', 'content' => 'How do you do?'),
		array('id' => 8, 'title' => 'eight', 'author' => 'Sean', 'content' => 'Bonjour'),
		array('id' => 9, 'title' => 'nine', 'author' => 'Scott', 'content' => 'what\'s up')
	);

	public function rest_auth(Rest $rest)
	{
		$user = Auth::instance()->get_user();
		return $rest->method() == 'OPTIONS' OR $user !== FALSE;
	}

	/**
	 * Cross-Origin Resource Sharing
	 *
	 * @param Rest $rest
	 */
	public function rest_cors(Rest $rest)
	{
		$origin = $rest->request()->headers('Origin');
		if (in_array($origin, self::$origin))
		{
			$rest->cors(array('origin' => $origin, 'creds' => 'true'));
		}
	}

	/**
	 * Cross-Origin Resource Sharing
	 *
	 * @param Rest $rest
	 */
	public function rest_options(Rest $rest)
	{
		$rest->send_code(200);
	}

	/**
	 * Returns test data
	 *
	 * @param Rest $rest
	 */
	public function rest_get(Rest $rest)
	{
		$data = Session::instance()->get('rest_test_data', $this->_data);
		$id = $rest->param('id');
		if ( ! empty($id))
		{
			if ( ! isset($data[$id]))
			{
				throw new Http_Exception_404('Resource not found, ID: :id', array(':id' => $id));
			}
			return $data[$id];

		}
		else
		{
			return $data;
		}
	}

	/**
	 *
	 * @param Rest $rest
	 */
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
			// TODO
			$rest->send_code(403); //Forbidden
		}
	}

	/**
	 *
	 * @param Rest $rest
	 */
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

	/**
	 *
	 * @param Rest $rest
	 */
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

}