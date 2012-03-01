<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * RESTful web service library.
 *
 * @package    jeremyf76/REST
 * @author     Jeremy Fowler
 * @copyright  (c) 2012 Jeremy Fowler
 * @license    http://www.opensource.org/licenses/BSD-3-Clause
 */

interface REST_Method_Patch extends REST_Model {
	public function rest_patch(Rest $rest);
}