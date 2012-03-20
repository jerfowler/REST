<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * RESTful web service library.
 *
 * @package    jerfowler/REST
 * @author     Jeremy Fowler
 * @copyright  (c) 2012 Jeremy Fowler
 * @license    http://www.opensource.org/licenses/BSD-3-Clause
 */

interface REST_Method_Delete extends REST_Model {
	public function rest_delete(Rest $rest);
}