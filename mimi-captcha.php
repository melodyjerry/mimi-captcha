<?php
/**
 * Plugin Name: Mimi Captcha
 * Plugin URI: https://github.com/stevenjoezhang/mimi-captcha
 * Description: 在WordPress登陆、注册或评论表单中加入验证码功能，支持字母、数字、中文和算术验证码。
 * Version: 0.0.5
 * Author: Shuqiao Zhang
 * Author URI: https://zhangshuqiao.org
 * Text Domain: mimi-captcha
 * Domain Path: /languages
 * License: GPL3
 */

/*  Copyright 2018  Shuqiao Zhang  (email : zsq@zsq.im)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

load_plugin_textdomain('mimi-captcha', false, dirname(plugin_basename(__FILE__)).'/languages');
define('MICAPTCHA_DIR_URL', plugin_dir_url(__FILE__));
//define('MICAPTCHA_DIR', dirname(__FILE__));
switch (get_option('micaptcha_loading_mode')) {
	case 'onload':
		define('MICAPTCHA_SCRIPT', '<script>
			window.addEventListener("load", function() {
				var captcha = document.getElementById("micaptcha");
				captcha.src = "'.MICAPTCHA_DIR_URL.'captcha.php?rand='.mt_rand().'";
				captcha.onclick = function() {
					captcha.src = "'.MICAPTCHA_DIR_URL.'captcha.php?rand=" + Math.random();
				}
			});
		</script>');
		break;
	case 'oninput':
		define('MICAPTCHA_SCRIPT', '<script>
			var captcha = document.getElementById("micaptcha"),
				MiCaptchaFlag = false;		
			function loadMiCaptcha() {
				if (MiCaptchaFlag) return;
				MiCaptchaFlag = true;
				captcha.src = "'.MICAPTCHA_DIR_URL.'captcha.php?rand='.mt_rand().'";
				captcha.onclick = function() {
					captcha.src = "'.MICAPTCHA_DIR_URL.'captcha.php?rand=" + Math.random();
				}
			}
			window.addEventListener("load", function() {
				var input = document.getElementsByTagName("input"),
					textarea = document.getElementsByTagName("textarea");
				for (var i = 0; i < input.length; i++) {
					input[i].addEventListener("input", loadMiCaptcha);
				}
				for (var i = 0; i < textarea.length; i++) {
					textarea[i].addEventListener("input", loadMiCaptcha);
				}
			});
			captcha.onclick = loadMiCaptcha;
		</script>');
		break;
	default:
		define('MICAPTCHA_SCRIPT', '<script>
			var captcha = document.getElementById("micaptcha");
			captcha.src = "'.MICAPTCHA_DIR_URL.'captcha.php?rand='.mt_rand().'";
			captcha.onclick = function() {
				captcha.src = "'.MICAPTCHA_DIR_URL.'captcha.php?rand=" + Math.random();
			}
		</script>');
		break;
}
define('MICAPTCHA_CONTENT', '<span style="display: block; clear: both;"></span>
		<img alt="Captcha Code" id="micaptcha" src="'.MICAPTCHA_DIR_URL.'default.png" style="max-width: 100%;"/>
		<span style="display: block; clear: both;"></span>
		<label>'.__('Click the image to refresh', 'mimi-captcha').'</label>
		<span style="display: block; clear: both;"></span>'.MICAPTCHA_SCRIPT);
define('MICAPTCHA_WHITELIST', '<p class="captcha-whitelist">
		<label>'.__('Captcha', 'mimi-captcha').' <span class="required">*</span></label>
		<div style="clear: both;"></div>
		<label>'.__('You are in the whitelist', 'mimi-captcha').'</label>
		</p>');
define('MICAPTCHA_INPUT', '<label for="url">'.__('Captcha', 'mimi-captcha').' <span class="required">*</span></label>
		<!-- Don`t Ask Why Not `for="captcha_code"`. You are Not Expected to Understand This. -->
		<input id="captcha_code" name="captcha_code" type="text" size="30" maxlength="200" autocomplete="off" style="display: block;" placeholder="'.__('Type the Captcha above', 'mimi-captcha').'"/>
		</p>');

//Hook to store the plugin status
register_activation_hook(__FILE__, 'micaptcha_enabled');
register_deactivation_hook(__FILE__, 'micaptcha_disabled');

//Hook to initalize the admin menu
add_action('admin_menu', 'micaptcha_admin_menu');

//Hook to initialize sessions
add_action('init', 'micaptcha_init_sessions');

//Hook to initialize admin notices
add_action('admin_notices', 'micaptcha_admin_notice');

add_filter('plugin_action_links', 'micaptcha_plugin_actions', 10, 2);

function micaptcha_enabled() {
	update_option('micaptcha_status', 'enabled');
}

function micaptcha_disabled() {
	update_option('micaptcha_status', 'disabled');
}

require_once('general-options.php');

//To add the menus in the admin section
function micaptcha_admin_menu() {
	add_options_page(
		__('Mimi Captcha'),
		__('Mimi Captcha'),
		'manage_options',
		'micaptcha_slug',
		'micaptcha_general_options'
	);
}

function micaptcha_init_sessions() {
	if (!session_id()) {
		session_start();
	}
	$_SESSION['captcha_type'] = get_option('micaptcha_type');
	$_SESSION['captcha_letters'] = get_option('micaptcha_letters');
	$_SESSION['total_no_of_characters'] = get_option('micaptcha_total_no_of_characters');
}

function micaptcha_admin_notice() {
	if (substr($_SERVER['PHP_SELF'], -11) == 'plugins.php' && function_exists('admin_url') && !get_option('micaptcha_type')) {
		echo '<div class="notice notice-warning"><p><strong>'.sprintf(__('Thank you for using Mimi Captcha. The plugin is not configured yet, please go to the <a href="%s">plugin admin page</a> to check settings.', 'mimi-captcha'), admin_url('options-general.php?page=micaptcha_slug')).'</strong></p></div>';
	}
}

function micaptcha_plugin_actions($links, $file) {
	if ($file == 'mimi-captcha/mimi-captcha.php' && function_exists('admin_url')) {
		$settings_link = '<a href="'.admin_url('options-general.php?page=micaptcha_slug').'">'.__('Settings', 'mimi-captcha').'</a>';
		array_unshift($links, $settings_link); //Before other links
	}
	return $links;
}

function micaptcha_get_ip() {
	$ip = '';
	if (isset($_SERVER)) {
		$server_vars = array('HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
		foreach ($server_vars as $var) {
			if (isset($_SERVER[$var]) && !empty($_SERVER[$var])) {
				if (filter_var($_SERVER[$var], FILTER_VALIDATE_IP)) {
					$ip = $_SERVER[$var];
					break;
				}
				else { //If proxy
					$ip_array = explode(',', $_SERVER[$var]);
					if (is_array($ip_array) && !empty($ip_array) && filter_var($ip_array[0], FILTER_VALIDATE_IP)) {
						$ip = $ip_array[0];
						break;
					}
				}
			}
		}
	}
	return $ip;
}

function micaptcha_ip_in_range($ip, $list) {
	if ($ip == "") return false;
	foreach ($list as $range) {
		$range = array_map('trim', explode('-', $range));
		if (count($range) == 1) {
			if ((string)$ip === (string)$range[0]) return true;
		}
		else {
			$low = ip2long($range[0]);
			$high = ip2long($range[1]);
			$needle = ip2long($ip);
			if ($low === false || $high === false || $needle === false) continue;

			$low = sprintf("%u", $low);
			$high = sprintf("%u", $high);
			$needle = sprintf("%u", $needle);
			if ($needle >= $low && $needle <= $high) return true;
		}
	}
	return false;
}

function micaptcha_whitelist() { //黑名单同理
	$whitelist_ips = get_option('micaptcha_whitelist_ips');
	//$whitelist_usernames = get_option('micaptcha_whitelist_usernames');
	if (micaptcha_ip_in_range(micaptcha_get_ip(), (array)$whitelist_ips)) return true;
	//else if (in_array($username, (array)$whitelist_usernames)) return true;
	else return false;
}

function micaptcha_validate() {
	if (micaptcha_whitelist()) return false;
	if (!isset($_SESSION['captcha_time']) || !isset($_SESSION['captcha_code']) || !isset($_REQUEST['captcha_code'])) {
		return __('Incorrect Captcha confirmation!', 'mimi-captcha');
	}
	//Captcha timeout
	if (get_option('micaptcha_timeout_time') && get_option('micaptcha_timeout_time') != '0') {
		if (time() - intval($_SESSION['captcha_time']) >= intval(get_option('micaptcha_timeout_time'))) {
			return __('Captcha timeout!', 'mimi-captcha');
		}
	}
	//If captcha is blank - add error
	if ($_REQUEST['captcha_code'] == "") {
		return __('Captcha cannot be empty. Please complete the Captcha.', 'mimi-captcha');
	}
	if ($_SESSION['captcha_code'] == $_REQUEST['captcha_code']) return false;
	if (get_option('micaptcha_case_sensitive') == 'insensitive') {
		if (strtoupper($_SESSION['captcha_code']) == strtoupper($_REQUEST['captcha_code'])) return false;
	}
	//Captcha was not matched
	return __('Incorrect Captcha confirmation!', 'mimi-captcha');
}

//Captcha for login authentication starts here
if (get_option('micaptcha_login') == 'yes') {
	add_action('login_form', 'micaptcha_login');
	add_filter('login_errors', 'micaptcha_login_errors');
	add_filter('login_redirect', 'micaptcha_login_redirect', 10, 3);
}

//Function to include captcha for login form
function micaptcha_login() {
	if (micaptcha_whitelist()) {
		echo MICAPTCHA_WHITELIST;
	}
	else {
		echo '<p class="login-form-captcha">'.MICAPTCHA_CONTENT;
		//Will retrieve the get varibale and prints a message from url if the captcha is wrong
		if (isset($_GET['captcha']) && $_GET['captcha'] == 'confirm_error') {
			echo '<label style="color: #FF0000;" id="capt_err">'.$_SESSION['captcha_error'].'</label><div style="clear: both;"></div>';
			$_SESSION['captcha_error'] = '';
		}
		echo MICAPTCHA_INPUT;
	}
	return true;
}

//Hook to find out the errors while logging in
function micaptcha_login_errors($errors) {
	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'register') {
		return $errors;
	}
	if (micaptcha_validate()) {
		return $errors.'<label id="capt_err" for="captcha_code_error">'.micaptcha_validate().'</label>';
	}
	return $errors;
}

//Hook to redirect after captcha confirmation
function micaptcha_login_redirect($url) {
	//Captcha mismatch
	if (isset($_SESSION['captcha_code']) && isset($_REQUEST['captcha_code']) && micaptcha_validate()) {
		$_SESSION['captcha_error'] = micaptcha_validate();
		wp_clear_auth_cookie();
		return $_SERVER["REQUEST_URI"]."/?captcha='confirm_error'";
		//登陆限制（IP或者用户名）应在此完成，使用数据库而非SESSION记录
	}
	//Captcha match: take to the admin panel
	else {
		return home_url('/wp-admin/');
	}
}

/* 
 * Add Password and Repeat Password fields to WordPress registration
 * All credit goes to http://thematosoup.com
 * Original code is from http://thematosoup.com/development/allow-users-set-password-wordpress-registration/
 * The page is gone, you can browse it via https://web.archive.org/web/20120618002355/http://thematosoup.com:80/development/allow-users-set-password-wordpress-registration
 */

if (get_option('micaptcha_password') == 'yes') {
	add_action('register_form', 'micaptcha_show_extra_register_fields');
	add_action('register_post', 'micaptcha_check_extra_register_fields', 10, 3);
	add_action('signup_extra_fields', 'micaptcha_show_extra_register_fields');
	add_action('user_register', 'micaptcha_register_extra_fields', 100);
	add_filter('gettext', 'micaptcha_edit_password_email_text', 20, 3);
}

function micaptcha_show_extra_register_fields() {
	?>
	<p>
		<label for="password"><?php _e('Password', 'mimi-captcha'); ?>
			<br/>
			<input id="password" class="input" type="password" tabindex="30" size="25" value="" name="password"/>
		</label>
	</p>
	<p>
		<label for="repeat_password"><?php _e('Repeat password', 'mimi-captcha'); ?>
			<br/>
			<input id="repeat_password" class="input" type="password" tabindex="40" size="25" value="" name="repeat_password"/>
		</label>
	</p>
	<?php
}

//Check the form for errors
function micaptcha_check_extra_register_fields($login, $email, $errors) {
	if (!isset($_POST['password']) || !isset($_POST['repeat_password']) || $_POST['password'] == '' || $_POST['repeat_password'] == '') {
		$errors->add('password_not_set', __('<strong>ERROR</strong>: ', 'mimi-captcha').__("Passwords cannot be empty.", 'mimi-captcha'));
		return $errors;
	}
	else if ($_POST['password'] !== $_POST['repeat_password']) {
		$errors->add('passwords_not_matched', __('<strong>ERROR</strong>: ', 'mimi-captcha').__("Passwords must match.", 'mimi-captcha'));
		return $errors;
	}
	else if (strlen($_POST['password']) < 8) {
		$errors->add('password_too_short', __('<strong>ERROR</strong>: ', 'mimi-captcha').__("Passwords must be at least eight characters long.", 'mimi-captcha'));
		return $errors;
	}
	return $errors;
}

//Storing WordPress user-selected password into database on registration
function micaptcha_register_extra_fields($user_id) {
	$userdata = array();
	
	$userdata['ID'] = $user_id;
	if (isset($_POST['password']) && $_POST['password'] !== '') {
		$userdata['user_pass'] = sanitize_text_field($_POST['password']); //Sanitize
	}
	wp_update_user($userdata);
}

//Editing WordPress registration confirmation message
function micaptcha_edit_password_email_text($translated_text, $untranslated_text, $domain) {
	if (in_array($GLOBALS['pagenow'], array('wp-login.php'))) {
		if ($untranslated_text == 'A password will be e-mailed to you.') {
			$translated_text = __('If you leave password fields empty one will be generated for you. Password must be at least eight characters long.', 'mimi-captcha');
			//邮件发送密码的方式已在WordPress中被弃用
		}
		elseif ($untranslated_text == 'Registration complete. Please check your email.' || $untranslated_text == 'Registration complete. Please check your e-mail.') {
			$translated_text = __('Registration complete. Please sign in or check your e-mail.', 'mimi-captcha');
		}
	}
	return $translated_text;
}

//Captcha for Register form starts here
if (get_option('micaptcha_register') == 'yes') {
	add_action('register_form', 'micaptcha_register');
	add_action('register_post', 'micaptcha_register_post', 10, 3);
	add_action('signup_extra_fields', 'micaptcha_register');
	add_filter('wpmu_validate_user_signup', 'micaptcha_register_validate');
}

//Function to include captcha for register form
function micaptcha_register($default) {
	if (micaptcha_whitelist()) {
		echo MICAPTCHA_WHITELIST;
	}
	else {
		echo '<p class="register-form-captcha">'.MICAPTCHA_CONTENT.MICAPTCHA_INPUT;
	}
	return true;
}

//This function checks captcha posted with registration
function micaptcha_register_post($login, $email, $errors) {
	if (micaptcha_validate()) {
		$errors->add('captcha_wrong', __('<strong>ERROR</strong>: ', 'mimi-captcha').micaptcha_validate());
	}
	return $errors;
}

function micaptcha_register_validate($results) {
	if (micaptcha_validate()) {
		$results['errors']->add('captcha_wrong', __('<strong>ERROR</strong>: ', 'mimi-captcha').micaptcha_validate());
		return $results;
	}
}

//Captcha for lost password form starts here
if (get_option('micaptcha_lost') == 'yes') {
	add_action('lostpassword_form', 'micaptcha_lostpassword');
	add_action('lostpassword_post', 'micaptcha_lostpassword_post', 10, 3);
}

//Function to include captcha for lost password form
function micaptcha_lostpassword($default) {
	if (micaptcha_whitelist()) {
		echo MICAPTCHA_WHITELIST;
	}
	else {
		echo '<p class="lost-form-captcha">'.MICAPTCHA_CONTENT.MICAPTCHA_INPUT;
	}
}

function micaptcha_lostpassword_post() {
	if (isset($_REQUEST['user_login']) && $_REQUEST['user_login'] == "") {
		return;
	}
	if (micaptcha_validate()) {
		wp_die(__('<strong>ERROR</strong>: ', 'mimi-captcha').micaptcha_validate().' '.__('Press your browser\'s back button and try again.', 'mimi-captcha'));
	}
}

//Captcha for Comments starts here
if (get_option('micaptcha_comments') == 'yes') {
	global $wp_version;
	if (version_compare($wp_version, '3', '>=')) { //wp 3.0 +
		add_action('comment_form_after_fields', 'micaptcha_comment_form_wp3', 1);
		add_action('comment_form_logged_in_after', 'micaptcha_comment_form_wp3', 1);
	}
	//For WP before WP 3.0
	add_action('comment_form', 'micaptcha_comment_form');	
	add_filter('preprocess_comment', 'micaptcha_comment_post');
}

//Function to include captcha for comments form
function micaptcha_comment_form() {
	if (micaptcha_whitelist()) {
		echo MICAPTCHA_WHITELIST;
	}
	else {
		if (is_user_logged_in() && get_option('micaptcha_registered') == 'yes') {
			return true;
		}
		echo '<p class="comment-form-captcha">'.MICAPTCHA_CONTENT.MICAPTCHA_INPUT;
	}
	return true;
}

//Function to include captcha for comments form > wp3
function micaptcha_comment_form_wp3() {
	if (micaptcha_whitelist()) {
		echo MICAPTCHA_WHITELIST;
	}
	else {
		if (is_user_logged_in() && get_option('micaptcha_registered') == 'yes') {
			return true;
		}
		echo '<p class="comment-form-captcha">'.MICAPTCHA_CONTENT.MICAPTCHA_INPUT;
	}
	remove_action('comment_form', 'micaptcha_comment_form');
	return true;
}

//Function to check captcha posted with the comment
function micaptcha_comment_post($comment) {
	if (is_user_logged_in() && get_option('micaptcha_registered') == 'yes') {
		//Skip capthca
		return $comment;
	}
	//Added for compatibility with WP Wall plugin
	//This does NOT add CAPTCHA to WP Wall plugin,
	//It just prevents the "Error: You did not enter a Captcha phrase." when submitting a WP Wall comment
	if (function_exists('WPWall_Widget') && isset($_REQUEST['wpwall_comment'])) {
		return $comment;
	}
	//Skip captcha for comment replies from the admin menu
	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'replyto-comment' &&
		(check_ajax_referer('replyto-comment', '_ajax_nonce', false) || check_ajax_referer('replyto-comment', '_ajax_nonce-replyto-comment', false))) {
		return $comment;
	}

	//Skip captcha for trackback or pingback
	if ($comment['comment_type'] != '' && $comment['comment_type'] != 'comment') {
		return $comment;
	}
	if (micaptcha_validate()) {
		wp_die(__('<strong>ERROR</strong>: ', 'mimi-captcha').micaptcha_validate().' '.__('Press your browser\'s back button and try again.', 'mimi-captcha'));
	}
	return $comment;
}
?>
