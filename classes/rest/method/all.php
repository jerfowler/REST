<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * RESTful web service library. 
 *
 * @package    jeremyf76/REST
 * @author     Jeremy Fowler
 * @copyright  (c) 2011 Jeremy Fowler
 * @license    http://www.opensource.org/licenses/BSD-3-Clause
 */
 
 interface REST_Method_All 
	extends REST_Method_Basic,
		REST_Method_Head,
		REST_Method_Trace,
		REST_Method_Options {}