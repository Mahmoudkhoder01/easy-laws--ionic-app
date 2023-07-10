<?php
class BWD_Htaccess{
	public function __construct(){
		add_action('admin_menu', array($this, 'menu'));
	}

	function menu(){
		add_submenu_page(NULL, '.htaccess Editor', 'HTAccess', 'can_bitwize', 'bwd_htaccess', array($this, 'page'));
	}

	function page(){
		$orig_path = ABSPATH.'.htaccess';
		echo '<div class="wrap">';

		if(!empty($_POST['submit']) AND !empty($_POST['save_htaccess']) AND check_admin_referer('bwhta_save', 'bwhta_save')){
			$new_content = $_POST['ht_content'];
			if($this->write($new_content)){
				echo'<div id="message" class="updated"><strong>File successfully changed</strong></div>';
			}
		}

		if(!file_exists($orig_path))
		{
			echo'<div class="wrap box">';
			echo'<pre class="red">'.__('Htaccess file does not exists!', 'wphe').'</pre>';
			echo'</div>';
			$success = false;
		}else{
			$success = true;
			if(!is_readable($orig_path))
			{
				echo'<div class="wrap box">';
				echo'<pre class="red">'.__('Htaccess file cannot read!', 'wphe').'</pre>';
				echo'</div>';
				$success = false;
			}
			if($success == true){
				@chmod($orig_path, 0644);
				$htaccess_content = @file_get_contents($orig_path, false, NULL);
				if($htaccess_content === false){
					echo'<div class="wrap box">';
					echo'<pre class="red">'.__('Htaccess file cannot read!', 'wphe').'</pre>';
					echo'</div>';
					$success = false;
				}else{
					$success = true;
				}
			}
		}

		if($success == true):
	?>
		<form method="post" action="">
			<input type="hidden" name="save_htaccess" value="save" />
			<?php wp_nonce_field('bwhta_save','bwhta_save'); ?>
			<h3 class="wphe-title"><?php _e('Content of the Htaccess file', 'wphe');?></h3>
			<textarea style="width:100%;" name="ht_content" wrap="off"><?php echo $htaccess_content;?></textarea>
			<p class="submit">
				<input type="submit" class="button button-primary" name="submit" value="Save file" />
			</p>
		</form>
		<script>
			!function(e,t){if("function"==typeof define&&define.amd)define(["exports","module"],t);else if("undefined"!=typeof exports&&"undefined"!=typeof module)t(exports,module);else{var n={exports:{}};t(n.exports,n),e.autosize=n.exports}}(this,function(e,t){"use strict";function n(e){function t(){var t=window.getComputedStyle(e,null);p=t.overflowY,"vertical"===t.resize?e.style.resize="none":"both"===t.resize&&(e.style.resize="horizontal"),c="content-box"===t.boxSizing?-(parseFloat(t.paddingTop)+parseFloat(t.paddingBottom)):parseFloat(t.borderTopWidth)+parseFloat(t.borderBottomWidth),isNaN(c)&&(c=0),i()}function n(t){var n=e.style.width;e.style.width="0px",e.offsetWidth,e.style.width=n,p=t,f&&(e.style.overflowY=t),o()}function o(){var t=window.pageYOffset,n=document.body.scrollTop,o=e.style.height;e.style.height="auto";var i=e.scrollHeight+c;return 0===e.scrollHeight?void(e.style.height=o):(e.style.height=i+"px",v=e.clientWidth,document.documentElement.scrollTop=t,void(document.body.scrollTop=n))}function i(){var t=e.style.height;o();var i=window.getComputedStyle(e,null);if(i.height!==e.style.height?"visible"!==p&&n("visible"):"hidden"!==p&&n("hidden"),t!==e.style.height){var r=d("autosize:resized");e.dispatchEvent(r)}}var s=void 0===arguments[1]?{}:arguments[1],a=s.setOverflowX,l=void 0===a?!0:a,u=s.setOverflowY,f=void 0===u?!0:u;if(e&&e.nodeName&&"TEXTAREA"===e.nodeName&&!r.has(e)){var c=null,p=null,v=e.clientWidth,h=function(){e.clientWidth!==v&&i()},y=function(t){window.removeEventListener("resize",h,!1),e.removeEventListener("input",i,!1),e.removeEventListener("keyup",i,!1),e.removeEventListener("autosize:destroy",y,!1),e.removeEventListener("autosize:update",i,!1),r["delete"](e),Object.keys(t).forEach(function(n){e.style[n]=t[n]})}.bind(e,{height:e.style.height,resize:e.style.resize,overflowY:e.style.overflowY,overflowX:e.style.overflowX,wordWrap:e.style.wordWrap});e.addEventListener("autosize:destroy",y,!1),"onpropertychange"in e&&"oninput"in e&&e.addEventListener("keyup",i,!1),window.addEventListener("resize",h,!1),e.addEventListener("input",i,!1),e.addEventListener("autosize:update",i,!1),r.add(e),l&&(e.style.overflowX="hidden",e.style.wordWrap="break-word"),t()}}function o(e){if(e&&e.nodeName&&"TEXTAREA"===e.nodeName){var t=d("autosize:destroy");e.dispatchEvent(t)}}function i(e){if(e&&e.nodeName&&"TEXTAREA"===e.nodeName){var t=d("autosize:update");e.dispatchEvent(t)}}var r="function"==typeof Set?new Set:function(){var e=[];return{has:function(t){return Boolean(e.indexOf(t)>-1)},add:function(t){e.push(t)},"delete":function(t){e.splice(e.indexOf(t),1)}}}(),d=function(e){return new Event(e)};try{new Event("test")}catch(s){d=function(e){var t=document.createEvent("Event");return t.initEvent(e,!0,!1),t}}var a=null;"undefined"==typeof window||"function"!=typeof window.getComputedStyle?(a=function(e){return e},a.destroy=function(e){return e},a.update=function(e){return e}):(a=function(e,t){return e&&Array.prototype.forEach.call(e.length?e:[e],function(e){return n(e,t)}),e},a.destroy=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],o),e},a.update=function(e){return e&&Array.prototype.forEach.call(e.length?e:[e],i),e}),t.exports=a});

			autosize(document.querySelectorAll('textarea'));
		</script>
	<?php
		endif;
		echo '</div>';
	}

	function write($new_content){
		$orig_path = ABSPATH.'.htaccess';
		@clearstatcache();

		if(file_exists($orig_path)) {
			if(is_writable($orig_path)) {
				@unlink($orig_path);
			}else{
				@chmod($orig_path, 0666);
				@unlink($orig_path);
			}
		}
		$new_content = trim($new_content);
		$new_content = str_replace('\\\\', '\\', $new_content);
		$new_content = str_replace('\"', '"', $new_content);
		$write_success = @file_put_contents($orig_path, $new_content, LOCK_EX);
		@chmod($orig_path, 0644);
		@clearstatcache();
		if(!file_exists($orig_path) && $write_success === false) {
			unset($orig_path);
			unset($new_content);
			unset($write_success);
			return false;
		}else{
			unset($orig_path);
			unset($new_content);
			unset($write_success);
			return true;
		}
	}
}
$GLOBALS['BWD_Htaccess'] = new BWD_Htaccess;
