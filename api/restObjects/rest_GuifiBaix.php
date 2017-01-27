<?php

/**
 * Rest GuifiBaix object
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
 * @author Victor Viudez <victor@guifibaix.coop>
 */

date_default_timezone_set("Europe/Madrid");

if(!defined('MAP_CLASS_BASE_PATH')) {
	define('MAP_CLASS_BASE_PATH', $_SERVER["DOCUMENT_ROOT"] . '/_classes/');
}

include MAP_CLASS_BASE_PATH . "mysql.class.php";

class GuifiBaix extends restObject {


	/**
	 *
	 * @param string $method
	 * @param array $request
	 * @param string $file
	 */
	public function __construct($method, $request = null, $file = null) {
		parent::__construct($method, $request, $file);
	}

    public function utf8ize($d) {
        if (is_array($d)) {
            foreach ($d as $k => $v) {
                $d[$k] = utf8ize($v);
            }
        } else if (is_string ($d)) {
            return utf8_encode($d);
        }
        return $d;
    }
    
	/**
	 * getData
	 * @return string
	 */
	public function getData() {
		if (!$this->isMethodCorrect('POST')) {
			return $this->getResponse();
		}
       
        $body = file_get_contents('php://input');
		if (empty($body)) { $res="ERROR: " . $body; }
		else {

            $json=$this->utf8ize($body);
            $json=str_replace("} error: submission to http://libremap.net/api/ failed (see syslog) {",",",$json);
            $nodo=json_decode($json,true);
     
                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        $dbSess = new MySQL(false,BBDD_NAME, BBDD_SERVER, BBDD_USER, BBDD_PWD, BBDD_CHARSET);
                        if (! $dbSess->Open(BBDD_NAME)) $dbSess->Kill();
                        //SET GLOBAL time_zone = 'Europe/Madrid';
                        //SET time_zone = 'Europe/Madrid'
                        $dbSess->Query("SET time_zone = 'Europe/Madrid'");
                        
						
						$row=$dbSess->QuerySingleRowArray("SELECT * FROM nodos WHERE Nodo='".$nodo["hostname"]."'");
						if (!$row){
							$dbSess->InsertRow("nodos",array("Nodo"=>"'".$nodo["hostname"]."'","MAC"=>"'".strtoupper($nodo["aliases"][0]["alias"])."'","JSON"=>"'".$json."'","Actualizado"=>"NOW()"));
						}
						else{
							// El nodo existe, pero si los datos no cambian, el timestamp no se actualiza, así que forzamos una modificación primero.
							$row=$dbSess->Query("UPDATE nodos SET Nodo='".$nodo["hostname"]."',MAC='',JSON='' WHERE Nodo='".$nodo["hostname"]."'");
							//$row=$dbSess->UpdateRows("nodos",array("Nodo"=>"'".$nodo["hostname"]."'","MAC"=>"''","JSON"=>"''"),array("Nodo"=>"'".$nodo["hostname"]."'"));
							if ($row) {
								//$row=$dbSess->UpdateRows("nodos",array("Nodo"=>"'".$nodo["hostname"]."'","MAC"=>"'".strtoupper($nodo["aliases"][0]["alias"])."'","JSON"=>"'".$json."'"),array("Nodo"=>"'".$nodo["hostname"]."'"));
								$row=$dbSess->Query("UPDATE nodos SET Nodo='".$nodo["hostname"]."',MAC='".strtoupper($nodo["aliases"][0]["alias"])."',JSON='".$json."' WHERE Nodo='".$nodo["hostname"]."'");
								if ($row) {
									$res="OK";
								}
								else {
									$res="Error on Update 1";
								}
							}
							else {
								$res="Error on Update 2";
							}
						}
	

						/*$row=$dbSess->AutoInsertUpdate("nodos",array("Nodo"=>"'".$nodo["hostname"]."'","MAC"=>"'".strtoupper($nodo["aliases"][0]["alias"])."'","JSON"=>"'".$json."'","Actualizado"=>"NOW()"),array("Nodo"=>"'".$nodo["hostname"]."'"));            
                        if ($row) {
							$res="OK";
						}
						else {
							$res="Error on AutoInsertUpdate";
						}*/
						
                    break;
                    case JSON_ERROR_DEPTH:
                        $res= ' - Maximum stack depth exceeded';
                    break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $res= ' - Underflow or the modes mismatch';
                    break;
                    case JSON_ERROR_CTRL_CHAR:
                        $res= ' - Unexpected control character found';
                    break;
                    case JSON_ERROR_SYNTAX:
                        $res= ' - Syntax error, malformed JSON';
                    break;
                    case JSON_ERROR_UTF8:
                        $res= ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                    default:
                        $res=' - Unknown error';
                    break;
                }
		}

		$this->respponse = $res;
		return $this->getResponse();
	}



	/**
	 * getLinks
	 * @return string
	 */
	public function getLinks() {
		if (!$this->isMethodCorrect('POST')) {
			return $this->getResponse();
		}
       
        $body = file_get_contents('php://input');
		if (empty($body)) { $res="ERROR: " . $body; }
		else {

            $json=$this->utf8ize($body);

			// Adaptación del JSON
			$json=str_replace('[26009        0] WARN  bmx_load_config: looking up bmx6.ipVersion.ipVersion failed','',$json);
			$json=str_replace('{ "status":','{"bmx6": [{	"status":',$json);						
			// Control por si el nodo no tiene enlaces
			if (strpos($json,'{ "links":') !== false){
				$json=str_replace('{ "links":',',{ "links":',$json);
			}
			$json=str_replace('{ "interfaces":',',{ "interfaces":',$json);
			$json=$json."]}";

			$res="";
			
            $nodo=json_decode($json,true);
			
                switch (json_last_error()) {					
                    case JSON_ERROR_NONE:
					
					// Control para nodos con versiones antiguas
					if (!isset($nodo["bmx6"][0]["status"]["name"])) {
						$gID=explode(".",$nodo["bmx6"][0]["status"]["globalId"]);
						$hostname=$gID[0];					
					}
					else {
						$hostname=$nodo["bmx6"][0]["status"]["name"];
					}
					$ipv4=$nodo["bmx6"][0]["status"]["tun4Address"];					
					
					if (!isset($nodo["bmx6"][1]["links"])) {
						$interface=$nodo["bmx6"][1]["interfaces"];
					}
					else{
						$interface=$nodo["bmx6"][2]["interfaces"];	
					}
					
					
						
					$llocal="0";
					foreach($interface as $int) {
						if ($int["type"]=="wireless"){
							$llocal=$int["llocalIp"];
						}								
					}
					
					
                        $dbSess = new MySQL(false,BBDD_NAME, BBDD_SERVER, BBDD_USER, BBDD_PWD, BBDD_CHARSET);
                        if (! $dbSess->Open(BBDD_NAME)) $dbSess->Kill();
                        //SET GLOBAL time_zone = 'Europe/Madrid';
                        //SET time_zone = 'Europe/Madrid'
                        $dbSess->Query("SET time_zone = 'Europe/Madrid'");
                        						
						$row=$dbSess->QuerySingleRowArray("SELECT * FROM nodos WHERE Nodo='".$hostname."'");
						if ($row){
							// El nodo existe, pero si los datos no cambian, el timestamp no se actualiza, así que forzamos una modificación primero.
							$row=$dbSess->Query("UPDATE nodos SET IPv4='', IPv6_LL='',JSONLinks='' WHERE Nodo='".$hostname."'");
							//$row=$dbSess->UpdateRows("nodos",array("Nodo"=>"'".$nodo["hostname"]."'","MAC"=>"''","JSON"=>"''"),array("Nodo"=>"'".$nodo["hostname"]."'"));
							if ($row) {
								//$row=$dbSess->UpdateRows("nodos",array("Nodo"=>"'".$nodo["hostname"]."'","MAC"=>"'".strtoupper($nodo["aliases"][0]["alias"])."'","JSON"=>"'".$json."'"),array("Nodo"=>"'".$nodo["hostname"]."'"));
								$row=$dbSess->Query("UPDATE nodos SET IPv4='".$ipv4."', IPv6_LL='".$llocal."',JSONLinks='".$json."' WHERE Nodo='".$hostname."'");
								if ($row) {
									$res="OK";
								}
								else {
									$res="Error on Update 1";
								}
							}
							else {
								$res="Error on Update 2";
							}
						}
	

						/*$row=$dbSess->AutoInsertUpdate("nodos",array("Nodo"=>"'".$nodo["hostname"]."'","MAC"=>"'".strtoupper($nodo["aliases"][0]["alias"])."'","JSON"=>"'".$json."'","Actualizado"=>"NOW()"),array("Nodo"=>"'".$nodo["hostname"]."'"));            
                        if ($row) {
							$res="OK";
						}
						else {
							$res="Error on AutoInsertUpdate";
						}*/
						
                    break;
                    case JSON_ERROR_DEPTH:
                        $res= ' - Maximum stack depth exceeded';
                    break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $res= ' - Underflow or the modes mismatch: '.json_last_error_msg().'('.$json.')';
                    break;
                    case JSON_ERROR_CTRL_CHAR:
                        $res= ' - Unexpected control character found'.json_last_error_msg().'('.$json.')';
                    break;
                    case JSON_ERROR_SYNTAX:
                        $res= ' - Syntax error, malformed JSON'.json_last_error_msg().'('.$json.')';
                    break;
                    case JSON_ERROR_UTF8:
                        $res= ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                    default:
                        $res=' - Unknown error';
                    break;
                }
		}

		$this->respponse = $res;
		return $this->getResponse();
	}

    
	/**
	 *
	 * @param integer $id
	 * @return array
	 */
	public function get($id) {
		$logged = $this->haveToBeLogged();
		if (true !== $logged) {
			return $logged;
		}

		if (!$this->isMethodCorrect('GET')) {
			return $this->getResponse();
		}

		$this->setIdFromRequest($id);
		$this->respponse = $this->getMyVars();
		return $this->getResponse();
	}


	private function setIdFromRequest($id) {
		if (is_array($id)) {
			$this->id = $id[0];
		} else {
			$this->id = $id;
		}
	}
}