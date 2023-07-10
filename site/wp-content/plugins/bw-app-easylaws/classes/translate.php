<?php
class App_Trans{
	protected static $_instance = null;
	public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self(); return self::$_instance;
    }

    public function go($str){
		$keywords = $this->get_keywords($str);
		$str_keywords = $this->get_keywords($str, true);
    	if($this->is_arabic($str)){
    		$is_arabic = true;
    		$provider = 'aratools';
    		// $term = $this->aratools($str);
    		$term = ''; // disable aratools
    	} else {
    		$is_arabic = false;
    		$provider = 'yamli';
    		// $term = $this->yamli($str);
    		// $keywords .= ' '.$this->get_keywords($term);
    		$term = '';
    	}

    	$query = $str.' '.$term.' '.$keywords;
    	$query = explode(' ', $query);
    	$query = array_unique($query);
    	$query = trim(implode(' ', $query));

    	return [
			'str'       => $str,
    		'is_arabic' => $is_arabic,
    		'provider'  => $provider,
    		'term'      => $term,
    		'keywords'  => $keywords,
    		'str_keywords'  => $str_keywords,
			'query'		=> $query,
			'search'    => $str.' '.$term,
    	];
    }

    public function get_keywords($words, $as_string = false){
    	if(empty($words)) return '';
    	$t = DB()->prefix.'app_keywords';
		$lines = [];
		$keywords = [];
		if($as_string){
			$ws = [trim($words)];
		} else {
			$ws = explode(' ', $words);
		}
		foreach($ws as $w){
			// $res = DB()->get_results("SELECT details FROM $t WHERE title LIKE '%$w%' OR details LIKE '%$w%'");
			$res = DB()->get_results("SELECT details FROM $t WHERE title LIKE '%$w%' OR FIND_IN_SET('$w', details)>0 OR FIND_IN_SET(' $w', details)>0 OR FIND_IN_SET('$w ', details)>0");
			foreach($res as $r){
				$lines[] = $r->details;
			}
		}
		foreach($lines as $line){
			$keys = explode(',', $line);
			$keywords = array_merge($keywords, $keys);
		}
		return trim(implode(' ', $keywords));
    }

	public function yamli($words){
		if(empty($words)) return '';
		$return = [];
		foreach(explode(' ', $words) as $w){
			$return[] = $this->__yamli($w).' ';
		}
		return trim(implode(' ', $return));
	}

	public function __yamli($word){
		$e = '';
		// $c = file_get_contents($f);
		$c = $this->__curl("http://api.yamli.com/transliterate.ashx?word={$word}&tool=api&account_id=000006&prot=http%3A&hostname=www.yamli.com&path=%2F&build=5515&sxhr_id=8");
		if(!$c) return $e;
		$r = str_ireplace(["if (typeof(Yamli) == 'object') {Yamli.I.SXHRData.dataCallback(", ");};"], ['', ''], $c);
		$json = json_decode($r, true);
		if(!$json) return $e;
		$data = $json['data'];
		$data = $data ? json_decode($data, true) : null;
		if(!$data) return $e;
		$trans = $data['r'];
		$w = explode('|', $trans)[0];
		$w = $w ? str_ireplace(['0/', '/0'], ['',''], $w) : '';
		return $w;
	}

	public function aratools($words){
		if(empty($words)) return '';

	    $return = [];
		foreach(explode(' ', $words) as $w){
			$o = $this->__aratools($w);
			$o = is_array($o) ? $this->hex2arabic($o['root']) : '';
			if($o) $return[] = $o;
		}
		return trim(implode(' ', $return));
	}

	public function __aratools($word){
		$response = $this->__curl('http://aratools.com/dict-service?_='.time().'&format=json&query='.json_encode([
			'word' => $word,
			'dictionary' => 'AR-EN-WORD-DICTIONARY',
			'dfilter' => true,
		]));
		if(!$response) return '';

		$output = '';
		$result = json_decode($response, true);
		if($result && isset($result['result'][0]) && isset($result['result'][0]['solution'])){
	        $output = $result['result'][0]['solution'];
	    }
	    return $output;
	    // [
	    // 	'form' => 'Original Word',
	    // 	'vocForm' => 'Vocabulary',
	    // 	'root' => 'Root',
	    // 	'niceGloss' => 'Glossary',
	    // ];
	}

	public function hex2arabic($str) {
	    return html_entity_decode("$str", ENT_COMPAT, "UTF-8");
	}

	public function uni2arabic($uni_str) {
	    for($i=0; $i<strlen($uni_str); $i+=4) {
	    	$new="&#x".substr($uni_str,$i,4).";";
	    	$txt = html_entity_decode("$new", ENT_COMPAT, "UTF-8");
	    	$All.=$txt;
	    }
	    return $All;
	}

	public function __curl($url){
		$url_parts = parse_url($url);
		$base = $url_parts['scheme'] . '://' . $url_parts['host'];

		$h = curl_init();
	    curl_setopt($h, CURLOPT_URL, $url);
	    curl_setopt($h, CURLOPT_POST, false);
	    curl_setopt($h, CURLOPT_ENCODING ,"");
	    // curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query([]));
	    curl_setopt($h, CURLOPT_SSL_VERIFYHOST, false);
	    curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($h, CURLOPT_REFERER, $base);
        curl_setopt($h, CURLOPT_HEADER, false);
	    curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($h, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($h, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.2309.372 Safari/537.36');

	    $response = curl_exec( $h );
	    curl_close($h);
	    return $response;
	}

	function uniord($u) {
		$k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
		return ord(substr($k, 1, 1)) * 256 + ord(substr($k, 0, 1));
	}

	function is_arabic($str) {
	    if(mb_detect_encoding($str) !== 'UTF-8') {
	        $str = mb_convert_encoding($str,mb_detect_encoding($str),'UTF-8');
	    }

	    preg_match_all('/.|\n/u', $str, $matches);
	    $chars = $matches[0];
	    $arabic_count = $latin_count = $total_count = 0;
	    foreach($chars as $char) {
	        //$pos = ord($char); we cant use that, its not binary safe
	        $pos = $this->uniord($char);
	        if($pos >= 1536 && $pos <= 1791) {
	            $arabic_count++;
	        } else if($pos > 123 && $pos < 123) {
	            $latin_count++;
	        }
	        $total_count++;
	    }
	    if(($arabic_count/$total_count) > 0.6) {// 60% arabic chars, its probably arabic
	        return true;
	    }
	    return false;
	}
}
function app_trans() {return App_Trans::instance();}
