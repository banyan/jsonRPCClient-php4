<?php

require_once('./lib/Jsphon-1.0.1/Jsphon/Decoder.php');
require_once('./lib/Jsphon-1.0.1/Jsphon/Encoder.php');

/*
					COPYRIGHT

Copyright 2007 Sergio Vaccaro <sergio@inservibile.org>

This file is part of JSON-RPC PHP.

JSON-RPC PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The object of this class are generic jsonRPC 1.0 clients
 * http://json-rpc.org/wiki/specification
 *
 * @author sergio <jsonrpcphp@inservibile.org>
 */
class jsonRPCClient {

	var $HOST_KEY           = 'Host';
	var $CONTENT_TYPE_KEY   = 'Content-Type';
	var $CONTENT_LENGTH_KEY = 'Content-Length';
	var $ACCEPT_KEY         = 'Accept';
	var $CONNECTION_KEY     = 'Connection';

	var $content_type = 'application/json';
	var $accept       = 'application/json,application/javascript,text/javascript';
	var $connection   = 'Close';

	/**
	 * Debug state
	 *
	 * @var boolean
	 */
	var $debug;

	/**
	 * The server URL
	 *
	 * @var string
	 */
	var $url;
	/**
	 * The request id
	 *
	 * @var integer
	 */
	var $id;
	/**
	 * If true, notifications are performed instead of requests
	 *
	 * @var boolean
	 */
	var $notification = false;

	/**
	 * Takes the connection parameters
	 *
	 * @param string $url
	 * @param boolean $debug
	 */
	function jsonRPCClient($url,$debug = false) {
		// server URL
        $this->url = parse_url($url);

		// proxy
		empty($proxy) ? $this->proxy = '' : $this->proxy = $proxy;
		// debug state
		empty($debug) ? $this->debug = false : $this->debug = true;
		// message id
		$this->id = 1;
	}

	/**
	 * Sets the notification state of the object. In this state, notifications are performed, instead of requests.
	 *
	 * @param boolean $notification
	 */
	function setRPCNotification($notification) {
		empty($notification) ?
							$this->notification = false
							:
							$this->notification = true;
	}

	/**
	 * Performs a jsonRPC request and gets the results as an array
	 *
	 * @param string $method
	 * @param array $params
	 * @return array
	 */
	function __call($method, $params) {

		// check
		if (!is_scalar($method)) {
			throw new Exception('Method name has no scalar value');
		}

		// check
		if (is_array($params)) {
			// no keys
			$params = array_values($params);
		} else {
			throw new Exception('Params must be given as array');
		}

		// sets notification or request task
		if ($this->notification) {
			$currentId = NULL;
		} else {
			$currentId = $this->id;
		}

		// prepares the request
		$request = array(
						'method' => $method,
						'params' => $params,
						'id' => $currentId
						);
		$params = JSON_RPC_Parser::encode($request);
		$this->debug && $this->debug.='***** Request *****'."\n".$request."\n".'***** End Of request *****'."\n\n";

        $req  = '';
        $req .= "POST {$this->url["path"]} HTTP/1.1\r\n";
        $req .= $this->create_header($this->HOST_KEY, $this->url['host']);
        $req .= $this->create_header($this->CONTENT_TYPE_KEY, $this->content_type);
        $req .= $this->create_header($this->CONTENT_LENGTH_KEY, strlen($params));
        $req .= $this->create_header($this->ACCEPT_KEY, $this->accept);
        $req .= $this->create_header($this->CONNECTION_KEY, $this->connection);
        $req .= "\r\n";
        $req .= $params;

        $fp = fsockopen("ssl://" . $this->url['host'], 443, $errno, $errstr, 30);

        fwrite($fp, $req);
        $response = '';
        while($row = fgets($fp)) {
            $response.= trim($row)."\n";
        }
        fclose($fp);
        $response = $this->http_parse_headers($response);

		// debug output
		if ($this->debug) {
			echo nl2br($debug);
		}

		// final checks and return
		if (!$this->notification) {
			// check
            //if ($response['id'] != $currentId) {
                //throw new Exception('Incorrect response id (request id: '.$currentId.', response id: '.$response['id'].')');
            //}
            if (!is_null($response['error'])) {
                throw new Exception('Request error: '.$response['error']);
            }

			return JSON_RPC_Parser::decode($response['result']);

		} else {
			return true;
		}
	}

    function http_parse_headers($headers=false) {
        if (!$headers) { return false; }
        $headers = str_replace("\r","",$headers);
        $headers = explode("\n",$headers);
        foreach($headers as $value){
            $header = explode(": ",$value);
            if($header[0] && !$header[1]){
                $headerdata['result'] = $header[0];
            }
            elseif($header[0] && $header[1]){
                $headerdata[$header[0]] = $header[1];
            }
        }
        return $headerdata;
    }

	function create_header($key, $value) {
		return "$key: $value\r\n";
	}
}

class JSON_RPC_Parser {
	function encode($val) {
        $jsonEncoder = new Jsphon_Encoder();
		return $jsonEncoder->encode($val);
	}
	function decode($val) {
        $jsonDecoder = new Jsphon_Decoder();
		return $jsonDecoder->decode($val);
	}
}

