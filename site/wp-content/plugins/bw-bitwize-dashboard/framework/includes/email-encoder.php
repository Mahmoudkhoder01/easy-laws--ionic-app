<?php
class BW_Email_Encoder
{
	public function __construct(){
		foreach (array('the_content', 'the_excerpt', 'widget_text', 'comment_text', 'comment_excerpt') as $filter) {
			add_filter($filter, array($this, 'encode_emails'), 1000);
		}
	}

	public function encode_emails($string) {

		if (strpos($string, '@') === false) return $string;

		$method = 'bw_email_encoder_encode_str';
		$regexp = apply_filters(
			'bw_email_encoder_regexp',
			'{
				(?:mailto:)?
				(?:
					[-!#$%&*+/=?^_`.{|}~\w\x80-\xFF]+
				|
					".*?"
				)
				\@
				(?:
					[-a-z0-9\x80-\xFF]+(\.[-a-z0-9\x80-\xFF]+)*\.[a-z]+
				|
					\[[\d.a-fA-F:]+\]
				)
			}xi'
		);
		return preg_replace_callback(
			$regexp,
			create_function(
	            '$matches',
	            'return '.$method.'($matches[0]);'
	        ),
			$string
		);
	}

}

function bw_email_encoder_encode_str($string) {
	$chars = str_split($string);
	$seed = mt_rand(0, (int) abs(crc32($string) / strlen($string)));
	foreach ($chars as $key => $char) {
		$ord = ord($char);
		if ($ord < 128) { // ignore non-ascii chars
			$r = ($seed * (1 + $key)) % 100; // pseudo "random function"
			if ($r > 60 && $char != '@') ; // plain character (not encoded), if not @-sign
			else if ($r < 45) $chars[$key] = '&#x'.dechex($ord).';'; // hexadecimal
			else $chars[$key] = '&#'.$ord.';'; // decimal (ascii)
		}
	}
	return implode('', $chars);
}

new BW_Email_Encoder;
