<?php
function header2array($raw){
	if(!preg_match_all('/([\w-]{1,}): (.{1,})/m', $raw, $header)){
		return false;
	}
	
	foreach($header[1] as $key => $fullmatch){
		$headers[strtolower($header[1][$key])] = $header[2][$key];
	}
	
	return $headers;
}

function curl($url, $head, $post, $redir){
	global $curl_extra_config;
	
	if(!filter_var($url, FILTER_VALIDATE_URL) || !is_array($head)){
		return false;
	}
	
	$ch = curl_init();
	
	$opt = array(
		CURLOPT_URL => $url,
		CURLOPT_HTTPHEADER => $head,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_AUTOREFERER => true,
		CURLOPT_HEADER => true,
		CURLINFO_HEADER_OUT => true,
		CURLOPT_CONNECTTIMEOUT => 7,
		CURLOPT_TIMEOUT => 15
	);
	
	if($post !== false){
		$opt[CURLOPT_POST] = true;
		$opt[CURLOPT_POSTFIELDS] = $post;
	}
	
	if($redir === true){
		$opt[CURLOPT_FOLLOWLOCATION] = true;
		$opt[CURLOPT_MAXREDIRS] = 10;
	}
	
	if(isset($curl_extra_config)){
		if(is_array($curl_extra_config)){
			foreach($curl_extra_config as $option => $value){
				$opt[$option] = $value;
			}
		}
	}
	
	curl_setopt_array($ch, $opt);
	
	$curlbody = curl_exec($ch);
	
	if(curl_error($ch)){
		if(curl_errno($ch) === 28){
			return false;
		}
		
		return curl_error($ch).' => '.curl_errno($ch);
	}
	
	$curlinfo = curl_getinfo($ch);
	curl_close($ch);
	
	if($curlinfo['http_code'] === 0){
		return false;
	}
	
	$info = array(
		'url' => $curlinfo['url'],
		'content_type' => $curlinfo['content_type'],
		'http_code' => $curlinfo['http_code'],
		'redirect_url' => $curlinfo['redirect_url']
	);
	
	$request_header = header2array($curlinfo['request_header']);
	$response_header = header2array(substr($curlbody, 0, $curlinfo['header_size']));
	
	$body = substr($curlbody, $curlinfo['header_size'], strlen($curlbody));
	
	return array(
		'info' => $info,
		'request_header' => $request_header,
		'response_header' => $response_header,
		'post' => $post,
		'body' => $body
	);
}