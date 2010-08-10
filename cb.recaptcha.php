<?php
/**
* reCAPTCHA Tab Class for handling CB Registrations
* @version $Id: cb.recaptcha.php 2010-08-06 iosoft $
* @package Community Builder
* @subpackage cb.recaptcha.php
* @author Mr. Ayan Debnath (aka: iosoft)
* @copyright © 2008 Future iOsoft Technology, INDIA
* @license http://creativecommons.org/licenses/by-nc-nd/2.5/in/
**/

defined('_VALID_MOS') or defined('_JEXEC') or defined('_VALID_CB') or die('Direct Access to this location is not allowed.');

error_reporting(0);
if(isset($_PLUGINS)) 
{
	/* LANGUAGE */
	if(!defined('_UE_CAPTCHA_Label'))		DEFINE('_UE_CAPTCHA_Label','Security Code');
	if(!defined('_UE_CAPTCHA_Desc'))		DEFINE('_UE_CAPTCHA_Desc','Enter Security Code from Image');
	if(!defined('_UE_CAPTCHA_NOT_VALID'))	DEFINE('_UE_CAPTCHA_NOT_VALID','Invalid Security Code');

	/* REGISTRATION */
	$_PLUGINS->registerFunction( 'onBeforeRegisterForm',		'onBeforeRegisterForm',			'getReCAPTCHAtab' );
	$_PLUGINS->registerFunction( 'onBeforeUserRegistration',	'onBeforeUserRegistration',		'getReCAPTCHAtab' );
	
	/* FORGOT PASSWORD */
	$_PLUGINS->registerFunction( 'onLostPassForm', 				'onLostPassForm',				'getReCAPTCHAtab' );
	$_PLUGINS->registerFunction( 'onLostPassForm', 				'onLostPassFormB',				'getReCAPTCHAtab' );
	$_PLUGINS->registerFunction( 'onBeforeNewPassword',			'onBeforeNewPassword',			'getReCAPTCHAtab' );
	$_PLUGINS->registerFunction( 'onStartNewPassword',			'onStartNewPassword',			'getReCAPTCHAtab' ); /* NEW */
	
	/* E-MAIL USER */
	$_PLUGINS->registerFunction( 'onAfterEmailUserForm', 		'onAfterEmailUserForm',			'getReCAPTCHAtab' );
	$_PLUGINS->registerFunction( 'onBeforeEmailUser',			'onBeforeEmailUser',			'getReCAPTCHAtab' );
	
	/* CB-CONTACT FORM */
	$_PLUGINS->registerFunction( 'onAfterEmailToContactForm', 	'onAfterEmailToContactForm',	'getReCAPTCHAtab' ); /* NEW */
	$_PLUGINS->registerFunction( 'onBeforeSendEmailToContact', 	'onBeforeSendEmailToContact',	'getReCAPTCHAtab' ); /* NEW */
	
}

class getReCAPTCHAtab extends cbTabHandler {
	
	/**   Constructor   **/	
	function getReCAPTCHAtab() { /* CHECKED */
		$this->cbTabHandler();
	}
	
	
	/**   Generates HTML code for reCAPTCHA   **/ /* Need Modification to support IE 6/7 : SPAN added */
	function _getHTMLcaptcha() {
		//global $_PLUGINS;
		
		$params = $this->params;
		require_once('recaptchalib.php'); /**    reCAPTCHA Library   **/

		$supportIE6 = "<!--[if IE]><script type=\"text/javascript\">onload=function(){if(document.getElementById('recaptcha_widget_div')==null)location.reload(true);}</script><![endif]-->";

		if($params->get('recaptchaTheme','red')=='custom')
		{		
		   $style = "\n<style type=\"text/css\"> .recaptchatable .recaptcha_image_cell, #recaptcha_table { background-color:".$params->get('recaptchaBackgroundRGB','#156c94')." !important; } #recaptcha_table { border-color: ".$params->get('recaptchaBorderRGB','#ffffff')." !important; } #recaptcha_response_field { border-color: #000000 !important;background-color:".$params->get('recaptchaTextBackRGB','#ffffff')." !important; }</style>";
		   $style.= "\n<script type=\"text/javascript\">var RecaptchaOptions = {theme : 'clean', lang : '" . $params->get('recaptchaLang','en') . "'};</script>";
		}
		else
		   $style = "\n<script type=\"text/javascript\">var RecaptchaOptions = {theme : '" . $params->get('recaptchaTheme','red') . "', lang : '" . $params->get('recaptchaLang','en') . "'};</script>\n";
		
		return "<span id=\"reCaptchaBlock\">" . $style . recaptcha_get_html($params->get('recaptchaPubKey','')) . "\n</span><br />&nbsp;" . $supportIE6; /**   Generating CORE reCAPTCHA form   **/
	}
	
	/**   Generates the HTML to display the registration tab/area   **/ /* Checked */
	function getDisplayRegistration($tab, $user, $ui) {
		//global $_PLUGINS;
		
		$params=$this->params;
        if(!$params->get('captchaRegistration',1))return;
		
		$return = "<tr class=\"tr_recpatcha\">";
		$return.= "<td class=\"titleCell\">" . htmlspecialchars(_UE_CAPTCHA_Label) . ":</td>";
		$return.= "<td class=\"fieldCell\">" . $this->_getHTMLcaptcha() . "</td>";
		$return.= "</tr>";
		
		return $return;
	}
	
	/**   Registration Form Submit   **/ /* Checked */
	function onBeforeUserRegistration( &$row, &$rowExtras ) {
		global $_PLUGINS;

		$params=$this->params;
        if(!$params->get('captchaRegistration',1))return;
		
		if(!session_id())session_start();
		require_once('recaptchalib.php');
		$resp = recaptcha_check_answer ($params->get('recaptchaPrvKey',''),
										$_SERVER["REMOTE_ADDR"],
										$_POST["recaptcha_challenge_field"],
										$_POST["recaptcha_response_field"]);
		
		if (!$resp->is_valid ) {
			$_PLUGINS->raiseError(0);
			$_PLUGINS->_setErrorMSG( _UE_CAPTCHA_NOT_VALID );
		}
		return true;
	}
	
	/**   This function is needed only to fix a bug in CB 1.0.2   **/ /* Checked */
	function onBeforeRegisterForm( $option, $emailpass, &$regErrorMSG, &$fieldsQuery ) {
		global $_PLUGINS;
		
		$params=$this->params;
        if(!$params->get('captchaRegistration',1))return;
		
		$_PLUGINS->_iserror=false;	// Bug fix for CB 1.0.2
	}

	
	
/* -------------------------------------------------------------------------------------------------------------------------- */

	
	/**   Lost Password Form   **/ /* Checked */
	function onLostPassForm( $ui ) {
		//global $_PLUGINS;

		$params=$this->params;
        if(!$params->get('captchaNewPassword',1))return;
        
		$return = array( 0 => htmlspecialchars(_UE_CAPTCHA_Label) . ':', 1 => $this->_getHTMLcaptcha());
		return $return;
	}
	
	/**   Lost Password Form-B   **/ /* Checked */
	function onLostPassFormB( $ui ) {
		//global $_PLUGINS;

		$params=$this->params;
        if(!$params->get('captchaNewPassword',1))return;
				
		$return = array( 0 => htmlspecialchars(_UE_CAPTCHA_Label) . ':', 1 => $this->_getHTMLcaptcha());
		return $return;
	}
	
	/**   Lost Password Form Submit   **/ /* Checked */
	function onBeforeNewPassword( $user_id, &$newpass, &$subject, &$message ) {
		global $_PLUGINS;
		
		$params=$this->params;
		if(!$params->get('captchaNewPassword',1))return;
				
		if(!session_id())session_start();
		require_once('recaptchalib.php');
		$resp = recaptcha_check_answer ($params->get('recaptchaPrvKey',''),
										$_SERVER["REMOTE_ADDR"],
										$_POST["recaptcha_challenge_field"],
										$_POST["recaptcha_response_field"]);

		if (!$resp->is_valid){
			$_PLUGINS->raiseError(0);
			$_PLUGINS->_setErrorMSG( _UE_CAPTCHA_NOT_VALID );
		}
		return true;	
	}

	/** Checks code entered during forgotten password form validation **/ /* ADDED New */
	function onStartNewPassword( &$checkUsername, &$confirmEmail) {
		global $_PLUGINS;
		
		$params=$this->params;
		if(!$params->get('captchaNewPassword',1))return;
		
		if(!session_id())session_start();
		require_once('recaptchalib.php');
		$resp = recaptcha_check_answer ($params->get('recaptchaPrvKey',''),
										$_SERVER["REMOTE_ADDR"],
										$_POST["recaptcha_challenge_field"],
										$_POST["recaptcha_response_field"]);

		if (!$resp->is_valid){
			$_PLUGINS->raiseError(0);
			$_PLUGINS->_setErrorMSG( _UE_CAPTCHA_NOT_VALID );
		}
		return true;
	}

	
/* -------------------------------------------------------------------------------------------------------------------------- */
	
	
	/**   Generates the HTML to display security image on forgotten email form   **/ /* Checked */
	function onAfterEmailUserForm() {
		//global $_PLUGINS;
		
		$params=$this->params;
        if(!$params->get('captchaEmailUser',1))return;
	
		return $this->_getHTMLcaptcha();
	}

	function onBeforeEmailUser( &$rowFrom, &$rowTo, $ui ) {
		global $_PLUGINS;
		
		$params=$this->params;
		if(!$params->get('captchaEmailUser',1))return;
		
		if(!session_id())session_start();
		require_once('recaptchalib.php');
		$resp = recaptcha_check_answer ($params->get('recaptchaPrvKey',''),
										$_SERVER["REMOTE_ADDR"],
										$_POST["recaptcha_challenge_field"],
										$_POST["recaptcha_response_field"]);

		if (!$resp->is_valid){
			$_PLUGINS->raiseError(0);
			$_PLUGINS->_setErrorMSG( _UE_CAPTCHA_NOT_VALID );
		}
		return true;
	}
	
	
/* -------------------------------------------------------------------------------------------------------------------------- */

	/* Form */
	function onAfterEmailToContactForm() {
		//global $_PLUGINS;
		
		$params=$this->params;
		if(!$params->get('captchaContactForm',1))return;
			
		return $this->_getHTMLcaptcha();
	}
	
	function onBeforeSendEmailToContact() {
		global $_PLUGINS;
		
		$params=$this->params;
		if(!$params->get('captchaContactForm',1))return;
		
		if(!session_id())session_start();	
		require_once('recaptchalib.php');
		$resp = recaptcha_check_answer ($params->get('recaptchaPrvKey',''),
										$_SERVER["REMOTE_ADDR"],
										$_POST["recaptcha_challenge_field"],
										$_POST["recaptcha_response_field"]);

		if (!$resp->is_valid){
			$_PLUGINS->raiseError(0);
			$_PLUGINS->_setErrorMSG( _UE_CAPTCHA_NOT_VALID );
		}
		return true;
	}

} // end class getReCAPTCHAtab.
?>
