<?php
/**
 * Projectname:   Simple rest server
 * Version:       1.0  
 * 
 * @author Corey Maynard <http://coreymaynard.com/>
 * @author Rafał Przetakowski <rprzetakowski@pr-projektos.pl>
 * 
 * 
 * GNU General Public License (Version 2, June 1991) 
 * 
 * This program is free software; you can redistribute 
 * it and/or modify it under the terms of the GNU 
 * General Public License as published by the Free 
 * Software Foundation; either version 2 of the License, 
 * or (at your option) any later version. 
 * 
 * This program is distributed in the hope that it will 
 * be useful, but WITHOUT ANY WARRANTY; without even the 
 * implied warranty of MERCHANTABILITY or FITNESS FOR A 
 * PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details. 
 * 
 */
class restServer {

	/**
	 * Property: method
	 * The HTTP method this request was made in, either GET, POST, PUT or DELETE
	 */
	protected $method = 'GET';

	/**
	 * Property: endpoint
	 * The object name requested in the URI. eg: /objectName
	 */
	protected $endpoint = '';

	/**
	 * Property: objectMethod
	 * The Object method requested in the URI. eg: /objectName/objectMethod
	 */
	protected $objectMethod = '';

	/*
	 * Property: registeredObjects
	 * Registered objects
	 */
	protected $registeredObjects = array();

	/**
	 * Property: verb
	 * An optional additional descriptor about the endpoint, used for things that can
	 * not be handled by the basic methods. eg: /files/process
	 */
	protected $verb = '';

	/**
	 * Property: args
	 * Any additional URI components after the endpoint and verb have been removed, in our
	 * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
	 * or /<endpoint>/<arg0>
	 */
	protected $args = Array();

	/**
	 * Property: file
	 * Stores the input of the PUT/POST from bash curl request
	 */
	protected $file = null;

	/**
	 * Constructor: __construct
	 * Allow for CORS, assemble and pre-process the data
	 */
	public function __construct($request, $origin) {
		header("Access-Control-Allow-Orgin: *");
		header("Access-Control-Allow-Methods: *");
		header("Content-Type: application/json");
		$this->args = explode('/', rtrim($request, '/'));

		$this->endpoint = array_shift($this->args);
		$this->objectMethod = array_shift($this->args);

		//if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
		//	$this->verb = array_shift($this->args);
		//}

		$this->method = $_SERVER['REQUEST_METHOD'];
		if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
			if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
				$this->method = 'DELETE';
			} else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
				$this->method = 'PUT';
			} else {
				throw new Exception("Unexpected Header");
			}
		}

		switch ($this->method) {
			case 'DELETE':
			case 'POST':
				$this->request = $this->_cleanInputs(( (empty($_POST)) ? $this->getJsonFromCURL() : $_POST));
				break;
			case 'GET':
				$this->request = $this->_cleanInputs($_GET);
				break;
			case 'PUT':
				$this->request = $this->_cleanInputs($_GET);
				$this->file = file_get_contents("php://input");
				break;
			default:
				$this->_response(array("Error" => 'Invalid Method'), 405);
				break;
		}
	}

	/**
	 * Register rest object
	 * @param string $objectName
	 */
	public function register($objectName) {
		if (class_exists($objectName)) {
			$this->registeredObjects[] = $objectName;
		}
	}

	/**
	 * 
	 * @return json object
	 */
	public function processAPI() {
		$className = $this->endpoint;
		if (!in_array($className, $this->registeredObjects)) {	
			return $this->_response(array("Error" => $this->_requestStatus(400)), 400);
		}

		$newObject = new $className($this->method, $this->request, $this->file);
		$objectMethods = get_class_methods($className);

		if ((int) method_exists($newObject, $this->objectMethod) > 0 && in_array($this->objectMethod, $objectMethods)) {
			$response = $newObject->{$this->objectMethod}($this->args);
			return $this->_response($response);
		}
		return $this->_response(array("Error" => $this->_requestStatus(400)), 400);
	}

	/**
	 * 
	 * @param array $data
	 * @param integer $status
	 * @return json object
	 */
	private function _response($data, $status = 200) {
		header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
		return json_encode($data);
	}

	/**
	 * 
	 * @param mixed $data
	 * @return mixed
	 */
	private function _cleanInputs($data) {
		$clean_input = Array();
		if (is_array($data)) {
			foreach ($data as $k => $v) {
				$clean_input[$k] = $this->_cleanInputs($v);
			}
		} else {
			$clean_input = trim(strip_tags($data));
		}
		return $clean_input;
	}

	/**
	 * 
	 * @return array
	 */
	private function getJsonFromCURL() {
		$fContent = file_get_contents("php://input");
		return json_decode($fContent, true);
	}

	/**
	 * 
	 * @param integer $statusCode
	 * @return string
	 */
	private function _requestStatus($statusCode) {
		$status = array(
		    100 => 'Continue',
		    101 => 'Switching Protocols',
		    200 => 'OK',
		    201 => 'Created',
		    202 => 'Accepted',
		    203 => 'Non-Authoritative Information',
		    204 => 'No Content',
		    205 => 'Reset Content',
		    206 => 'Partial Content',
		    300 => 'Multiple Choices',
		    301 => 'Moved Permanently',
		    302 => 'Found',
		    303 => 'See Other',
		    304 => 'Not Modified',
		    305 => 'Use Proxy',
		    306 => '(Unused)',
		    307 => 'Temporary Redirect',
		    400 => 'Bad Request - GuifiBaix Map REST API',
		    401 => 'Unauthorized',
		    402 => 'Payment Required',
		    403 => 'Forbidden',
		    404 => 'Not Found',
		    405 => 'Method Not Allowed',
		    406 => 'No Aceptable',
		    407 => 'Proxy Authentication Required',
		    408 => 'Request Timeout',
		    409 => 'Conflict',
		    410 => 'Gone',
		    411 => 'Length Required',
		    412 => 'Precondition Failed',
		    413 => 'Request Entity Too Large',
		    414 => 'Request-URI Too Long',
		    415 => 'Unsupported Media Type',
		    416 => 'Requested Range Not Satisfiable',
		    417 => 'Expectation Failed',
		    500 => 'Internal Server Error',
		    501 => 'Not Implemented',
		    502 => 'Bad Gateway',
		    503 => 'Service Unavailable',
		    504 => 'Gateway Timeout',
		    505 => 'HTTP Version Not Supported');
		return ($status[$statusCode]) ? $status[$statusCode] : $status[500];
	}

}
