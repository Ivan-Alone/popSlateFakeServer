<?php
	$t = file_get_contents('php://input');
	
	$input_data = @json_decode($t, true);
	$answer = null;
	
	
	$time = time();
	
	$session_storage = 'sessionStorage.json';
	$accounts_storage = 'accounts.json';
	
	
	$sessions = @json_decode(json_get_contents($session_storage), true);
	$accounts = @json_decode(json_get_contents($accounts_storage), true);
	
	switch($_SERVER['REDIRECT_URL']) {
		case '/parse/users':
			// {"curated":false,"username":"root@linux.lc","password":"qwertyuiop","email":"root@linux.lc","displayName":"Ivan_Alone"}
			
			if (getUserAccount($accounts, 'user', $input_data['displayName']) != null || getUserAccount($accounts, 'email', $input_data['username'])) {
				$answer = '{"code":202,"error":"Account already exists for this username."}';
				http_response_code(403);
				break;
			}
			
			$accounts[] = array('email' => $input_data['username'], 'user' => $input_data['displayName'], 'password' => md5($input_data['password']), 'time' => $time);
			
		case '/parse/login':	
			$user_obj = getUserAccount($accounts, 'email', $input_data['username']);
			
			$date_info = date('Y-m-d', $time).'T'.date('H:i:s', $time).'Z';
			$date_info_create = date('Y-m-d', $user_obj['time']).'T'.date('H:i:s', $user_obj['time']).'Z';
			
			if ($user_obj == null || $user_obj['password'] != md5($input_data['password'])) {
				$answer = '{"code":101,"error":"Invalid username/password."}';
				http_response_code(403);
				break;
			}
			
			$session = md5(mt_rand(100000,999999).$input_data['username'].mt_rand(100000,999999).$time.mt_rand(100000,999999));
			$object = substr(md5(substr($session, 8, 16)), 11, 10);
			
			
			$answer = '{"username":"'.$input_data['username'].'","_method":"GET","createdAt":"'.$date_info_create.'","updatedAt":"'.$date_info.'","objectId":"'.$object.'","sessionToken": "'.$session.'"}';
			
			$sessions[$session] = array('email' => $input_data['username'], 'user' => $user_obj['user'], 'object' => $object);
			
			
		break;
		case '/parse/classes/Feed':
			$session = getCurrentUser();
			if ($session == null) {
				$answer = '{}';
				break;
			}
			// X-Parse-Session-Token
			// {"include":"featuredUsers,images.user,trendingImages.user,users.exploreImages","order":"-createdAt","where":{"createdAt":{"$gt":{"iso":"2018-03-25T07:55:59.170Z","__type":"Date"}}},"limit":"4","_method":"GET"}
			$answer = $t;
		break;
		case '/parse/classes/_User':
			// {"count":"1","where":{"displayName":"Ivan_Alone"},"_method":"GET","limit":"0"}
			if (getUserAccount($accounts, 'user', $input_data['displayName']) == null)
				$answer = '{"results":[],"count":0}';
			else 
				$answer = '{}';
		break;
		case '/parse/classes/Feature':
			// {"where":{"name":{"$in":["slideshow","imageNotification"]}},"_method":"GET"}
			$answer = $t;
		break;
		case '/parse/files/profileImage.png':
			$session = getCurrentUser();
			if ($session == null) {
				$answer = '{}';
				break;
			}
			@mkdir($session['user']);
			if (file_put_contents($session['user'].'/profile.jpg', $t)) {
				
			} else {
				
			}
			$answer = '{"status":"OK","size":"'.strlen($t).'"}';
		break;
		case '/parse/files/coverImage.png':
			$session = getCurrentUser();
			if ($session == null) {
				$answer = '{}';
				break;
			}
			@mkdir($session['user']);
			if (file_put_contents($session['user'].'/cover.jpg', $t)) {
				
			} else {
				
			}
			$answer = '{"status":"OK","size":"'.strlen($t).'"}';
		break;
		case '/parse/functions/generateExploreFeeds':
			// {}
			$answer = $t;;
		break;
		case '/parse/classes/_Installation':
			// {"timeZone":"Europe\/Moscow","deviceToken":"7d5eb4ef1f821f7f90c893a07cdee741738ff7ace6bcac8856459aa7382d456f","deviceType":"ios","appVersion":"1874","appName":"popSlate","channels":["global"],"installationId":"d405ca51-5b3d-4231-8335-487789d337e4","appIdentifier":"com.popslate.popSlate.a","parseVersion":"1.13.0","localeIdentifier":"ru-RU","badge":0}
			// {"associatedUsers":{"objectId":"09edb1868a","className":"_User","__type":"Pointer"}}
			$answer = $t;
		break;
		case '/parse/requestPasswordReset':
			// {"email":"root@time.com"}
			$answer = $t;
		break;
		case '/parse/classes/Image':
			// {"include":"user","order":"-updatedAt","where":{"user":{"$select":{"key":"toUser","query":{"where":{"type":"follow","fromUser":{"objectId":"09edb1868a","className":"_User","__type":"Pointer"},"toUser":{"$ne":{"objectId":"09edb1868a","className":"_User","__type":"Pointer"},"$exists":true}},"className":"Activity"}}}},"limit":"10","_method":"GET"}
			// {"include":"featuredUsers,images.user,trendingImages.user,users.exploreImages","order":"-createdAt","where":{"createdAt":{"$gt":{"iso":"2018-03-25T11:13:18.704Z","__type":"Date"}}},"limit":"4","_method":"GET"}
			
			$answer = '{"images.user":"/Alexei Navalny/profile.jpg"}';
		break;
		case '/parse/classes/Activity':
			// {"where":{"type":"follow","fromUser":{"objectId":"09edb1868a","className":"_User","__type":"Pointer"},"toUser":{"$ne":{"objectId":"09edb1868a","className":"_User","__type":"Pointer"},"$exists":true}},"include":"fromUser","_method":"GET"}
			$answer = $t;
		break;
		
		/*case '':
			// 
		break;*/
		
		default:
			$answer = $t;
	}
	
	if (@$input_data['username'] != null && @$input_data['username'] != null) {
		
	}
	
	echo $answer;
	//file_put_contents('teqtest'.$time.'.'.rand(10000,99999).'.lcf', $t.PHP_EOL.$answer.PHP_EOL.getprint($_SERVER));
	
	file_put_contents($session_storage, json_encode($sessions));
	file_put_contents($accounts_storage, json_encode($accounts));
	
	function getCurrentUser() {
		global $sessions;
		$headers = getallheaders();
		if (@$headers['X-Parse-Session-Token'] == null) return null;
		if (is_array(@$sessions[$headers['X-Parse-Session-Token']])) {
			return $sessions[$headers['X-Parse-Session-Token']];
		}
		return null;
	}
	
	function getprint($var) {
		ob_start();
		print_r($var);
		return ob_get_clean();
	}
	
	function getUserAccount($database, $key, $value) {
		foreach ($database as $user) {
			if ($user[$key] == $value) {
				return $user;
			}
		}
		return null;
	}
	
	function json_get_contents($file) {
		$data = @file_get_contents($file);
		if ($data === false) return false;
		if ($data == null) {
			return '{}';
		}
		return $data;
	}