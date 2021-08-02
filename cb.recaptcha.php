<?php
/**
 * CB reCAPTCHA
 * @version $Id: cb.recaptcha.php 2008-07-30 iosoft $
 * @package Community Builder
 * @subpackage cb.recaptcha.php
 * @author Mr. Ayan Debnath (aka: iosoft)
 * @copyright © 20021 Future iOsoft Technology, INDIA. All rights reserved.
 * @license http://creativecommons.org/licenses/by-nc-nd/2.5/in/
 *
 */
defined('_VALID_MOS') or defined('_JEXEC') or defined('_VALID_CB') or die;

use Joomla\CMS\Captcha\Google\HttpBridgePostRequestMethod;
use Joomla\Utilities\IpHelper;

global $_PLUGINS;
if ($_PLUGINS) {

	$_PLUGINS->registerFunction('onBeforeRegisterForm', 'onBeforeRegisterForm','getReCAPTCHAtab');
    
	$_PLUGINS->registerFunction('onBeforeUserRegistration', 'onBeforeUserRegistration','getReCAPTCHAtab');
    
	///////////////////////////////////////////////////////////////////////////
    
	$_PLUGINS->registerFunction('onLostPassForm', 'onLostPassForm','getReCAPTCHAtab');
    
	$_PLUGINS->registerFunction('onBeforeNewPassword', 'onBeforeNewPassword','getReCAPTCHAtab');
    
	///////////////////////////////////////////////////////////////////////////
    
	$_PLUGINS->registerFunction('onAfterEmailUserForm', 'onAfterEmailUserForm', 'getReCAPTCHAtab');
    
	$_PLUGINS->registerFunction('onBeforeEmailUser', 'onBeforeEmailUser', 'getReCAPTCHAtab');
	
	///////////////////////////////////////////////////////////////////////////
	
	$_PLUGINS->registerFunction('onBeforeUsernameReminder', 'onBeforeUsernameReminder', 'getReCAPTCHAtab');
}

class getReCAPTCHAtab extends cbTabHandler {
	const RECAPTCHA_API_SECURE_SERVER = "https://www.google.com/recaptcha/api";
	const RECAPTCHA_VERIFY_SERVER = "www.google.com";
	
    /** Constructor **/
    function getReCAPTCHAtab() {   
        $this->cbTabHandler();
		
		//$this->loadLanguage();
		$language = JFactory::getLanguage();
		$language->load('plg_captcha_recaptcha', JPATH_ADMINISTRATOR);
    }
	
	/** Fail-safe Params Setup **/
	private function params() {
		if ($this->params->get('public_key') == '' || $this->params->get('private_key') == '') {
			
			$joomla_recaptcha_plugin = JPluginHelper::getPlugin('captcha', 'recaptcha');  
			$joomla_recaptcha_plugin_params = new JRegistry($joomla_recaptcha_plugin->params);
			
			if ($this->params->get('public_key') == '' && $joomla_recaptcha_plugin_params->get('public_key') != '') $this->params->set('public_key', trim($joomla_recaptcha_plugin_params->get('public_key')));
			
			if ($this->params->get('private_key') == '' && $joomla_recaptcha_plugin_params->get('private_key') != '') $this->params->set('private_key', trim($joomla_recaptcha_plugin_params->get('private_key')));
		}
		return $this->params;
	}
	
	private function _recaptcha_https_post($host, $path, $data, $port = 443) {
		
		$url = 'https://' . $host . $path;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_PORT, $port );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
		// Get the response and close the channel.
		$response = curl_exec($ch);
		curl_close($ch);
		$response = json_decode($response);
		
		return $response;
	}
	
    /** Generates HTML code for reCAPTCHA **/
    private function getHTMLrecaptcha() {
        $params = $this->params();
		
		
        //$document = JFactory::getDocument();
        //$document->addCustomTag('<script src="https://www.google.com/recaptcha/api.js" async defer></script>');
		
		$language = JFactory::getLanguage();
		$tag = explode('-', $language->getTag());
		$tag = $tag[0];
		JHtml::_('script', self::RECAPTCHA_API_SECURE_SERVER.'.js?hl='.$tag.'" async defer="defer');
		
        $html = '<div class="g-recaptcha" data-sitekey="'.trim($params->get('public_key')).'" data-theme="'.trim($params->get('theme')).'" data-size="'.trim($params->get('size')).'"></div>';
		$html .= '<div><small style="font-size:9px;">Community Builder is secured by <a href="https://www.gigahertz.net.in/tutorials/tips-tricks/120-joomla-community-builder-recaptcha-plugin" target="_blank" title="CB reCAPTCHA">CB reCAPTCHA</a>.</small></div>';
        return $html;
    }
	
	private function reCAPTCHA_validate() {
		global $_PLUGINS;
        $params = $this->params();
		
		$remoteip = JRequest::getVar('X_FORWARDED_FOR', $_SERVER["HTTP_X_FORWARDED_FOR"]);
		if (empty($remoteip)) {
			$remoteip = JRequest::getVar('REMOTE_ADDR', '', 'SERVER');
		}
		if (empty($remoteip)) {
			//$this->_subject->setError(JText::_('PLG_RECAPTCHA_ERROR_NO_IP'));
			$_PLUGINS->raiseError(0);
            $_PLUGINS->_setErrorMSG(JText::_('PLG_RECAPTCHA_ERROR_NO_IP'));
			return false;
		}
		
		$private_key = $params->get('private_key');
		if (empty($private_key)) {
			//$this->_subject->setError(JText::_('PLG_RECAPTCHA_ERROR_NO_PRIVATE_KEY'));
			$_PLUGINS->raiseError(0);
            $_PLUGINS->_setErrorMSG(JText::_('PLG_RECAPTCHA_ERROR_NO_PRIVATE_KEY'));
			return false;
		}

		$response = JRequest::getString('g-recaptcha-response');
		// Discard spam submissions
		if ($response == null || strlen($response) == 0) {
			//$this->_subject->setError(JText::_('PLG_RECAPTCHA_ERROR_EMPTY_SOLUTION'));
			$_PLUGINS->raiseError(0);
            $_PLUGINS->_setErrorMSG(JText::_('PLG_RECAPTCHA_ERROR_EMPTY_SOLUTION'));
			return false;
		}
		
		$response = $this->_recaptcha_https_post(
			self::RECAPTCHA_VERIFY_SERVER,
			"/recaptcha/api/siteverify",
			array(
				'secret'	=> $private_key,
				'remoteip'	=> $remoteip,
				'response'	=> $response
			)
		);
		
		//echo '<pre>'; print_r($response); echo '</pre>';
		return $response;		
	}
	
    /** Generates the HTML to display the registration tab/area **/
    function getDisplayRegistration($tab, $user, $ui, $postdata=null) {
        $params = $this->params();
		
        if (!$params->get('captchaRegistration', 1)) return;
		
        $html = '<div class="form-group row no-gutters sectiontableentry1 cbft_predefined cbtt_input cb_form_line" id="cbfr_recaptcha">
			<label id="cblabrecaptcha" class="col-form-label col-sm-3 pr-sm-2" style="cursor:default !important;"></label>
			<div class="cb_field col-sm-9">
				<div id="cbfv_recaptcha">'.$this->getHTMLrecaptcha().'</div>
			</div>
		</div>';
        return $html;
    }
	
    /**   Registration Form Submit   **/
    function onBeforeUserRegistration(&$row, &$rowExtras) {
        global $_PLUGINS;
        $params = $this->params();
		
		if (!$params->get('captchaRegistration', 1)) return;
		
		
		//$language = JFactory::getLanguage();
		//$language->load('plg_captcha_recaptcha', JPATH_ADMINISTRATOR);
		
		$response = $this->reCAPTCHA_validate();
		if($response === false) {
			return false;
		} elseif ( $response->{'success'} == '1') {
			return true;
		} else {
			//@todo use exceptions here
			
			//$this->_subject->setError(JText::_('PLG_RECAPTCHA_ERROR_'.strtoupper(str_replace('-', '_', $response->{'error-codes'}[0]))));
			//return false;
			
			$_PLUGINS->raiseError(0);
            $_PLUGINS->_setErrorMSG(JText::_('PLG_RECAPTCHA_ERROR_'.strtoupper(str_replace('-', '_', $response->{'error-codes'}[0]))));
			return false;
		}
    }
	
    /** This function is needed only to fix a bug in CB 1.0.2 (hopefully with next version this could be removed). */
    function onBeforeRegisterForm($option, $emailpass, &$regErrorMSG, &$fieldsQuery) {
		global $_PLUGINS;
        $params = $this->params();
        if (!$params->get('captchaRegistration', 1)) return;
        $_PLUGINS->_iserror = false; // ugly bug fix of CB 1.0.2
    }
	
    /** Lost Password Form **/
    function onLostPassForm($ui) {
        $params = $this->params();
        if (!$params->get('captchaNewPassword', 1)) return;
		
        $return = array(0 => '', 1 => $this->getHTMLrecaptcha());
        return $return;
    }

    /** Lost Password Form Submit **/
    function onBeforeNewPassword($user_id, &$newpass, &$subject, &$message) {
		global $_PLUGINS;
        $params = $this->params();
		
		if (!$params->get('captchaNewPassword', 1)) return;
		
		$response = $this->reCAPTCHA_validate();
		if($response === false) {
			return false;
		} elseif ( $response->{'success'} == '1') {
			return true;	
		} else {
			//@todo use exceptions here
			
			$_PLUGINS->raiseError(0);
            $_PLUGINS->_setErrorMSG(JText::_('PLG_RECAPTCHA_ERROR_'.strtoupper(str_replace('-', '_', $response->{'error-codes'}[0]))));
			return false;
		}
        return true;
    }
	
    /**   Generates the HTML to display security image on forgotten email form   **/
    function onAfterEmailUserForm(&$rowFrom, &$rowTo, &$warning, $ui) {
        $params = $this->params();
        
		if (!$params->get('captchaEmailUser', 1)) return;
		
        return $this->getHTMLrecaptcha();
    }
	
    function onBeforeEmailUser(&$rowFrom, &$rowTo, $ui) {
		global $_PLUGINS;
        $params = $this->params();
		
		if (!$params->get('captchaEmailUser', 1)) return;
		
		$response = $this->reCAPTCHA_validate();
		if($response === false) {
			return false;
		} elseif ( $response->{'success'} == '1') {
			return true;	
		} else {
			//@todo use exceptions here
			
			$_PLUGINS->raiseError(0);
            $_PLUGINS->_setErrorMSG(JText::_('PLG_RECAPTCHA_ERROR_'.strtoupper(str_replace('-', '_', $response->{'error-codes'}[0]))));
			return false;
		}
        return true;
    }
		
	function onBeforeUsernameReminder($ui, &$subject, &$message) {
		global $_PLUGINS;
        $params = $this->params();
		
		/*
		$captchatype = $params->get('captchatype', 'myCaptcha');
		if($captchatype == 'myCaptcha') {
		  $checkarray = array('word' => $_POST['cb_mycaptcha'], 'ip' => CbmycaptchaModel::GetUserIp());
		} else {
		 $privatekey = $params->get('privatekey','');
		 $checkarray = array('privatekey' => $privatekey, 'rec_ch_field' => $_POST["recaptcha_challenge_field"],'rec_res_field' => $_POST["recaptcha_response_field"]);
		}
		
        $res = CbmycaptchaModel::checkCode($checkarray,$captchatype);
		if (!$res) {
			$_PLUGINS->raiseError(0);
			$_PLUGINS->_setErrorMSG(_UE_CAPTCHA_NOT_VALID);
		}
		*/
		
		$response = $this->reCAPTCHA_validate();
		if($response === false) {
			return false;
		} elseif ( $response->{'success'} == '1') {
			return true;	
		} else {
			//@todo use exceptions here
			
			$_PLUGINS->raiseError(0);
            $_PLUGINS->_setErrorMSG(JText::_('PLG_RECAPTCHA_ERROR_'.strtoupper(str_replace('-', '_', $response->{'error-codes'}[0]))));
			return false;
		}
        
		return true;
	}
    
} // end class getReCAPTCHAtab.
?>
