<?php
/**
* reCAPTCHA Tab Class for handling CB Registrations
* @version $Id: cb.recaptcha.php 2008-07-30 iosoft $
* @package Community Builder
* @subpackage cb.recaptcha.php
* @author Mr. Ayan Debnath (aka: iosoft)
* @copyright © 2008 Future iOsoft Technology, INDIA
* @license http://creativecommons.org/licenses/by-nc-nd/2.5/in/
**/

defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

if(isset($_PLUGINS)) 
{
	if(!defined('_UE_CAPTCHA_Label'))		DEFINE('_UE_CAPTCHA_Label','Security Code');
	if(!defined('_UE_CAPTCHA_Desc'))		DEFINE('_UE_CAPTCHA_Desc','Enter Security Code from image');
	if(!defined('_UE_CAPTCHA_NOT_VALID'))	DEFINE('_UE_CAPTCHA_NOT_VALID','Invalid Security Code');

	$_PLUGINS->registerFunction( 'onBeforeRegisterForm',		'onBeforeRegisterForm',			'getReCAPTCHAtab' );
	$_PLUGINS->registerFunction( 'onBeforeUserRegistration',	'onBeforeUserRegistration',		'getReCAPTCHAtab' );
	$_PLUGINS->registerFunction( 'onLostPassForm', 				'onLostPassForm',				'getReCAPTCHAtab' );
	$_PLUGINS->registerFunction( 'onBeforeNewPassword',			'onBeforeNewPassword',			'getReCAPTCHAtab' );
	$_PLUGINS->registerFunction( 'onAfterEmailUserForm', 		'onAfterEmailUserForm',			'getReCAPTCHAtab' );
	$_PLUGINS->registerFunction( 'onBeforeEmailUser',			'onBeforeEmailUser',			'getReCAPTCHAtab' );
}

class getReCAPTCHAtab extends cbTabHandler {
	
	/**   Constructor   **/	
	function getReCAPTCHAtab() {
		$this->cbTabHandler();
	}

	/**   Generates HTML code for reCAPTCHA   **/
	function _getHTMLcaptcha() {
		
		require_once('recaptchalib.php'); /**    reCAPTCHA Library   **/
		
		$params = $this->params;

		if($params->get('recaptchaTheme','red')=='custom')
		{		
		   $style = "\n<style type=\"text/css\"> .recaptchatable .recaptcha_image_cell, #recaptcha_table { background-color:".$params->get('recaptchaBackgroundRGB','#156c94')." !important; } #recaptcha_table { border-color: ".$params->get('recaptchaBorderRGB','#ffffff')." !important; } #recaptcha_response_field { border-color: #000000 !important;background-color:".$params->get('recaptchaTextBackRGB','#ffffff')." !important; }</style>";
		   $style.= "\n<script type=\"text/javascript\">var RecaptchaOptions = {theme : 'clean', lang : '" . $params->get('recaptchaLang','en') . "'};</script>\n";
		}
		else
		   $style = "\n<script type=\"text/javascript\">var RecaptchaOptions = {theme : '" . $params->get('recaptchaTheme','red') . "', lang : '" . $params->get('recaptchaLang','en') . "'};</script>\n";
		
		return $style . recaptcha_get_html($params->get('recaptchaPubKey','')) . "<br />&nbsp;"; /**   Generating CORE reCAPTCHA form   **/
	}
	
	/**   Generates the HTML to display the registration tab/area   **/
	function getDisplayRegistration($tab, $user, $ui) {

		$params = $this->params;
        if (!$params->get('captchaRegistration',1)) {
        	return;
		}
		
		$return = "<tr>";                                              
		$return .= "<td class=\"titleCell\">" . _UE_CAPTCHA_Label . ":</td>";
		$return .= "<td class=\"fieldCell\">" . $this->_getHTMLcaptcha() . "</td>";
		$return .= "</tr>";
		
		return $return;
	}
	
	/**   Registration Form Submit   **/
	function onBeforeUserRegistration( &$row, &$rowExtras ) {
		global $ueConfig, $mainframe, $_PLUGINS;
				
		if ( ! session_id() ) {
			session_start();
		}
		
		require_once('recaptchalib.php');
		$params = $this->params;
		$resp = recaptcha_check_answer ($params->get('recaptchaPrvKey',''),
										$_SERVER["REMOTE_ADDR"],
										$_POST["recaptcha_challenge_field"],
										$_POST["recaptcha_response_field"]);
		
		if ( ! $resp->is_valid ) {
			$_PLUGINS->raiseError(0);
			$_PLUGINS->_setErrorMSG( _UE_CAPTCHA_NOT_VALID );
		}
		return true;
	}
	
	/**   This function is needed only to fix a bug in CB 1.0.2 (hopefully with next version this could be removed).   */
	function onBeforeRegisterForm( $option, $emailpass, &$regErrorMSG, &$fieldsQuery ) {
		global $_PLUGINS;
		
		$params = $this->params;
        if (!$params->get('captchaRegistration',1)) {
        	return;
		}
		
		$_PLUGINS->_iserror = false;	// ugly bug fix of CB 1.0.2
	}
	
	/**   Lost Password Form   **/
	function onLostPassForm( $ui ) {

		$params = $this->params;
        if (!$params->get('captchaNewPassword',1)) {
        	return;
		}

		$return = array( 0 => _UE_CAPTCHA_Label . ':', 1 => $this->_getHTMLcaptcha() );
		return $return;
	}
	
	/**   Lost Password Form Submit   **/
	function onBeforeNewPassword( $user_id, &$newpass, &$subject, &$message ) {
		global $ueConfig, $mainframe, $_PLUGINS;
		
		if ( ! session_id() ) {
			session_start();
		}
		
		require_once('recaptchalib.php');
		$params = $this->params;
		$resp = recaptcha_check_answer ($params->get('recaptchaPrvKey',''),
										$_SERVER["REMOTE_ADDR"],
										$_POST["recaptcha_challenge_field"],
										$_POST["recaptcha_response_field"]);

		if ( ! $resp->is_valid ) {
			$_PLUGINS->raiseError(0);
			$_PLUGINS->_setErrorMSG( _UE_CAPTCHA_NOT_VALID );
		}
		return true;	
	}

	/**   Generates the HTML to display security image on forgotten email form   **/
	function onAfterEmailUserForm( &$rowFrom, &$rowTo, &$warning, $ui ) {
    	global $mosConfig_live_site;

		$params = $this->params;
        if (!$params->get('captchaEmailUser',1)) {
        	return;
		}
	
		return $this->_getHTMLcaptcha();
	}

	function onBeforeEmailUser( &$rowFrom, &$rowTo, $ui ) {
		global $ueConfig, $mainframe, $_PLUGINS;
		
		if ( ! session_id() ) {
			session_start();
		}

		require_once('recaptchalib.php');
		$params = $this->params;
		$resp = recaptcha_check_answer ($params->get('recaptchaPrvKey',''),
										$_SERVER["REMOTE_ADDR"],
										$_POST["recaptcha_challenge_field"],
										$_POST["recaptcha_response_field"]);

		if ( ! $resp->is_valid ) {
			$_PLUGINS->raiseError(0);
			$_PLUGINS->_setErrorMSG( _UE_CAPTCHA_NOT_VALID );
		}
		return true;	
	}

} // end class getReCAPTCHAtab.
?>
