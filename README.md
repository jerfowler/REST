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

'''
class Controller_Template_REST extends Controller
	implements REST_Content_HTML,
		REST_Content_JSON,
		REST_Content_XML,
		REST_Content_CSV {
'''

###Models handle the HTTP methods
Each model must implement one or more of the REST_Method Interfaces

'''
class Model_REST_Users
	implements REST_CORS,
		REST_Method_Get,
		REST_Method_Post {
'''