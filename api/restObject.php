<?php

/**
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
 * @author Rafał Przetakowski <rprzetakowski@pr-projektos.pl>
 */
abstract class restObject {

	/**
	 * Request method
	 * @var string 
	 */
	protected $method;
	
	/**
	 * Request array
	 * @var array 
	 */
	protected $request;
	
	/**
	 * Stores the input of the PUT/POST from bash curl request
	 * @var string
	 */
	protected $file;
	
	/**
	 *
	 * @var array 
	 */
	protected $respponse = array();
	
	/**
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * 
	 * @param string $method
	 * @param array $request
	 * @param string $file
	 */
	public function __construct($method, $request = null, $file = null) {
		$this->method = $method;
		$this->request = $request;
		$this->file = $file;
	}

	/**
	 * 
	 * @return boolean
	 */
	public function haveToBeLogged() {
		if (!isset($_SESSION['auth_id'])) {
			$this->setError("401 Unauthorized");
			return $this->getResponse();
		} else {
			return true;
		}
	}

	/**
	 * 
	 * @return array
	 */
	protected function getResponse() {
		$response = array("response" => $this->respponse, "errors" => $this->errors);
		return $response;
	}

	/**
	 * 
	 * @param string $errorMessage
	 */
	protected function setError($errorMessage) {
		$this->errors[] = $errorMessage;
	}

	/**
	 * 
	 * @param string $method
	 * @return boolean
	 */
	protected function isMethodCorrect($method) {
		if ($this->method != $method) {
			$this->setError('Only accepts ' . $method . ' requests');
			return false;
		}
		return true;
	}

	/**
	 * 
	 * @return array
	 */
	protected function showMyPublicsOnly() {
		$me = $this;
		$publics = function() use ($me) {
			return get_object_vars($me);
		};
		return $publics();
	}

	/**
	 * 
	 * @param array $myVars
	 * @return array
	 */
	protected function getMyVars($myVars = null) {
		if (empty($myVars)) {
			$myVars = $this->showMyPublicsOnly();
		}

		if (is_array($myVars)) {
			/*
			 * Return array converted to object
			 * Using __METHOD__ (Magic constant)
			 * for recursive call
			 */
			return array_map(__METHOD__, $myVars);
		} else {
			// Return array
			return $myVars;
		}
	}

}
