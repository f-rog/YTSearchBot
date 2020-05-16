<?php
define('BOT_TOKEN', ''); // replace
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
define('BOT_USERNAME', ''); // replace

require('curl.php');

function catchRequest() {
    $json = file_get_contents("php://input");
    if(!$request = json_decode($json)){
        return false;
    }
    return $request;
}

if(!$REQUEST = catchRequest()){
	exit;
}

// end of reception
if(strlen($REQUEST->message->text) < 5){
	exit;
}

function sendMessage($chatId, $text) {
    $query = http_build_query([
      'chat_id'=> $chatId,
      'text'=> $text
    ]);
  
    $response = file_get_contents(API_URL.'sendMessage?'.$query);
    return $response;
}

function youtubeMusicSearch($query){
	$link = 'https://music.youtube.com/youtubei/v1/search?alt=json&key=AIzaSyC9XL3ZjWddXya6X74dJoCTL-WEYFDNX30'; // <= Public API Key
	
	$head = array(
		'Accept: */*',
		'Accept-Language: en-US',
		'Content-Type: application/json',
		'X-YouTube-Client-Name: 67',
		'X-YouTube-Client-Version: 0.1',
		'X-YouTube-Utc-Offset: -360',
		'X-YouTube-Time-Zone: America/Mexico_City',
		'Origin: https://music.youtube.com',
		'Referer: https://music.youtube.com/'
	);
	
	$post = '{"context":{"client":{"clientName":"WEB_REMIX","clientVersion":"0.1","hl":"es-419","gl":"MX","experimentIds":[],"experimentsToken":"","utcOffsetMinutes":-360,"locationInfo":{"locationPermissionAuthorizationStatus":"LOCATION_PERMISSION_AUTHORIZATION_STATUS_UNSUPPORTED"},"musicAppInfo":{"musicActivityMasterSwitch":"MUSIC_ACTIVITY_MASTER_SWITCH_INDETERMINATE","musicLocationMasterSwitch":"MUSIC_LOCATION_MASTER_SWITCH_INDETERMINATE","pwaInstallabilityStatus":"PWA_INSTALLABILITY_STATUS_CAN_BE_INSTALLED"}},"capabilities":{},"request":{"internalExperimentFlags":[{"key":"force_music_enable_outertube_tastebuilder_browse","value":"true"},{"key":"force_music_enable_outertube_search_suggestions","value":"true"},{"key":"force_music_enable_outertube_playlist_detail_browse","value":"true"}],"sessionIndex":0},"activePlayers":{},"user":{"enableSafetyMode":false}},"query":"'.$query.'","suggestStats":{"validationStatus":"VALID","parameterValidationStatus":"VALID_PARAMETERS","clientName":"youtube-music","originalQuery":"'.$query.'","availableSuggestions":[{"index":0,"type":0},{"index":1,"type":0},{"index":2,"type":0},{"index":3,"type":0},{"index":4,"type":0},{"index":5,"type":0},{"index":6,"type":0}],"zeroPrefixEnabled":true}}';
	
	if(!$req = curl($link, $head, $post, false)){
		return false;
	}
	
	if($req['info']['http_code'] !== 200){
		return $req['info']['http_code'];
	}
	
	$response = json_decode($req['body'], true);
	
 	if(isset($response['contents']['sectionListRenderer']['contents'][0]['musicShelfRenderer']['contents'][0]['musicResponsiveListItemRenderer']['overlay']['musicItemThumbnailOverlayRenderer']['content']['musicPlayButtonRenderer']['playNavigationEndpoint']['watchEndpoint']['videoId'])){
		return $response['contents']['sectionListRenderer']['contents'][0]['musicShelfRenderer']['contents'][0]['musicResponsiveListItemRenderer']['overlay']['musicItemThumbnailOverlayRenderer']['content']['musicPlayButtonRenderer']['playNavigationEndpoint']['watchEndpoint']['videoId'];
	}
		
	return false;
}

function normalSearch($query){
    $q = http_build_query(
        ['search_query' => $query]
    );
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:76.0) Gecko/20100101 Firefox/76.0\r\n" .
                "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9\r\n"
        ]
    ];
    
    $context = stream_context_create($opts);
    $response = file_get_contents('https://youtube.com/results?'.$q,false,$context);

    if (preg_match('/\/watch\?v=[\w\d-]+/',$response,$coincidencias)) {
        return $coincidencias[0];
    } else {
        return "No hubo resultados para tu busqueda :(";
    }
}

// send message
$message_id = $REQUEST->message->message_id;
$chat_id = $REQUEST->message->chat->id;
$text = $REQUEST->message->text;

function yt_search($query){
    if (strpos($query,'-')){
        $search = youtubeMusicSearch($query);
        if ($search){
            return 'https://www.youtube.com/watch?v='.$search;
        } else {
            return 'No hubo resultados.';
        }
    } else {
        $search = normalSearch($query);
        if ($search) {
		return 'https://www.youtube.com'.$search;
	} else { 
		return 'Hubo un error';
	}
    }
}

switch ($REQUEST->message->chat->type) {
    case 'private':
        if (strpos($text, "/start") === 0) {
            sendMessage($chat_id,'Hola, envia tu busqueda.');
        } else {
            sendMessage($chat_id,yt_search($text));
        }
    case 'supergroup':
        if(strpos($text, "@".BOT_USERNAME) === 0){
            $new = str_replace("@".BOT_USERNAME." ",'', $text);
            sendMessage($chat_id,yt_search($new));

	}
}
?>
