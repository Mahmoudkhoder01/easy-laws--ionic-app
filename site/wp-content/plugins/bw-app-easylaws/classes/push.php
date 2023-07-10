<?php
class App_Push
{

	protected static $_instance = null;
	public static function instance(){
		if (is_null(self::$_instance)) {self::$_instance = new self();}return self::$_instance;
	}

	public function send($content, $args =[]){
		global $APP;
		$args = wp_parse_args($args, [
			'url' => false,
			'data' => false,
			'filters' => false,
			'include_player_ids' => false,
			'title' => '',
		]);
		try {
			if (!function_exists('curl_init')) return false;
			$app_id = $APP['onesignal_app_id'];
			$rest_api_key = $APP['onesignal_rest_api_key'];
			if(!$app_id || !$rest_api_key) return false;

			$content = $this->decode_entities($content);
			$title = $args['title'] ? $this->decode_entities($args['title']) : $this->decode_entities(get_bloginfo('name'));

			$fields = array(
				'app_id'            => $app_id,
				'headings'          => ["en" => $title],
				'contents'          => ["en" => $content],
				'included_segments' => ['All'],
				'isAnyWeb'          => true,
				'isIos'				=> true,
				'isAndroid'			=> true,
			);

			if($args['data']) $fields['data'] = $args['data'];
			if($args['url']) $fields['data'] = $args['url'];
			
			if($args['filters']){
				$fields['filters'] = [];
				foreach($args['filters'] as $k => $v){
					if(is_array($v)){
						foreach($v as $a => $b){
							$fields['filters'][] = [
								'field' => 'tag',
								'relation' => '=',
								'key' => $k,
								'value' => $b,
							];
							$fields['filters'][] = ['operator' => 'OR'];
						}
					} else {
						$fields['filters'][] = [
							'field' => 'tag',
							'relation' => '=',
							'key' => $k,
							'value' => $v,
						];
						$fields['filters'][] = ['operator' => 'OR'];
					}
				}
				array_pop($fields['filters']); // remove last operator
			}

			if($args['include_player_ids']) {
				$fields['include_player_ids'] = $args['include_player_ids'];
				unset($fields['included_segments']);
				unset($fields['isAnyWeb']);
				unset($fields['isIos']);
				unset($fields['isAndroid']);
				unset($fields['filters']);
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json; charset=utf-8',
				'Authorization: Basic ' . $rest_api_key,
			));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			$response = curl_exec($ch);
			$curl_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($curl_http_code != 200) {
				$this->debug('There was a ' . $curl_http_code . ' error sending your notification');
				return false;
			} else {
				$parsed_response = json_decode($response, true);
				return $parsed_response;
				// $recipient_count   = $parsed_response['recipients'];
				// $sent = array_key_exists('send_after', $fields) ? 'scheduled' : 'sent';
			}
			curl_close($ch);
		} catch (Exception $e) {
			$this->debug('Caught Exception:', $e->getMessage());
		}
	}

	public function decode_entities($string) {
		if(!$string) return '';
		$string = strip_tags($string);
		$HTML_ENTITY_DECODE_FLAGS = ENT_QUOTES;
		if (defined('ENT_HTML401')) {
			$HTML_ENTITY_DECODE_FLAGS = ENT_HTML401 | $HTML_ENTITY_DECODE_FLAGS;
		}
		return html_entity_decode(str_replace("&apos;", "'", $string), $HTML_ENTITY_DECODE_FLAGS, 'UTF-8');
	}

	public function debug() {
  		$numargs  = func_num_args();
  		$arg_list = func_get_args();
  		$bt       = debug_backtrace();
  		$output   = '[' . $bt[1]['function'] . '] ';
  		for ($i = 0; $i < $numargs; $i ++) {
    		$arg = $arg_list[ $i ];

    		if (is_string($arg)) {
      			$arg_output = $arg;
    		} else {
      			$arg_output = var_export($arg, true);
    		}

    		if ($arg === "") {
      			$arg_output = "\"\"";
    		} else if ($arg === null) {
      			$arg_output = "null";
    		}

    		$output = $output . $arg_output . ' ';
  		}
  		$output = substr($output, 0, - 1);
  		$output = substr($output, 0, 1024); // Restrict messages to 1024 characters in length
  		error_log('OneSignal: ' . $output);
	}

}
function app_push(){ return App_Push::instance(); }
