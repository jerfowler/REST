<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * RESTful web service library. 
 *
 * @package    jeremyf76/REST
 * @author     Jeremy Fowler
 * @copyright  (c) 2011 Jeremy Fowler
 * @license    http://www.opensource.org/licenses/BSD-3-Clause
 */
 
 interface REST_Method_Basic 
	extends REST_Method_Get,
		REST_Method_Put,
		REST_Method_Post,
		REST_Method_Delete {}