<?php
class ShibbolethAuth extends BaseAuth
{
	var $sessionHeaders = array('Shib-Session-ID', 'HTTP_SHIB_IDENTITY_PROVIDER');
	var $sessionInitiatorHandler = '/Shibboleth.sso/Login';
	var $logoutHandler = '/Shibboleth.sso/Logout';
	var $usernameAttribute = 'eppn';
	var $roleAttribute = 'affiliation';
	var $defaultRole = 'user';
	var $roleMapping = array('faculty@ubc.ca' => 'admin',
				 'staff@ubc.ca' => 'admin',
				 'student@ubc.ca' => 'viewer');

	public function authenticate()
	{
		if(!$this->isSessionActive()) {
			header("Location: ".$this->getSessionInitiatorUrl());
			return false;
		}

		// do authentication using CAS, POST params should contain the values
		$user = new BaseUser($this->mapUsername($_SERVER[$this->usernameAttribute]), 
                                     $this->mapRole($_SERVER[$this->roleAttribute]));

		if(!session_id()) {
			// compatible with older version that doesn't have startSession
			if(method_exists($this, 'startSession')) {
				$this->startSession(); 
			} else {
				session_start();
			}
		}
		$_SESSION['user'] = $user;
		
		return true;
	}
	
	public function authenticationResponse($result)
	{
		if($result)
		{
			$redirect = isset($_SESSION['redirect']) ? $_SESSION['redirect'] : HttpHelper::getBaseURL();
			header("Location: ".$redirect);
		}
		else
		{
			echo 'You have successfully logged through Shibboleth. But you do not have access this appliction.';
		}
	}
	
	public function logout()
	{
		session_destroy();
		return true;
	}
	
	/**
	 * should be called after logout and act upon it (redirect, output, other)
	 *
	 * @param $result - what was the result of calling logout()
	 */
	public function logoutResponse($result)
	{
		if ( $this->isSessionActive() ) {
			$suffix = $this->logoutHandler.'?return='.HttpHelper::getBaseURL();
		} else {
			$suffix = '';
		}
		//header("Location: " . self::getServerURL() . $suffix);
		header("Refresh: 3; url=" . self::getServerURL() . $suffix);
		echo "You have successfully logged out and will be redirected to the homepage in 3 seconds.";
	}


 	public function isSessionActive() { 
		$active = false;

		foreach ($this->sessionHeaders as $header) {
			if ( array_key_exists($header, $_SERVER) && !empty($_SERVER[$header]) ) {
				$active = true;
				break;
			}
		}
		return $active;
	}

	/**
	 * Generate the URL to initiate Shibboleth login.
	 *
	 * @param string $redirect the final URL to redirect the user to after all login is complete
	 * @return the URL to direct the user to in order to initiate Shibboleth login
	 */
	public function getSessionInitiatorUrl($redirect = null) {
		$initiator_url = self::getServerURL() . $this->sessionInitiatorHandler . 
			(null == $redirect ? '' : '?target=' . $redirect);
		return $initiator_url;
	}

	public function mapUsername($username) {
		return substr($username, 0, strpos($username, '@'));
	}

	public function mapRole($roles) {
		$roles = explode(';', $roles);
		foreach($this->roleMapping as $key => $role) {
			if(in_array($key, $roles)) return $role;
		}
		return $this->defaultRole;
	}

	public static function getServerURL() {
		return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'];
	}

	public static function getLoginURL() {
		return Config::get("loginUrl").'?target='.HttpHelper::getBaseURL().'auth.php';
	}

	// override the default function to generate the Shibboleth URL.
	public function getLoginRedirectUrl()
	{
		$_SESSION['redirect'] = $_SERVER["REQUEST_URI"];
		return self::getLoginURL();
	}
}
