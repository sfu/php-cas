<?php namespace SFU;

/**
 * SFU CAS library for PHP: CAS class
 *
 * Fifth-generation simplified SFU CAS authentication solution for PHP.
 *
 * @author Mike Sollanych, Ross Cawston
 * @package sfu/php-cas
 */

class CAS {

	/**
	 * Check Login Status.
	 * @return boolean True if the user is logged in, false otherwise.
	 */
	public static function checkLoginStatus($maillist = false) {
		// Is maillist membership required?
		if ($maillist) {
			// Yes. Is the user authenticated to CAS with this maillist?
			if (!array_key_exists('cas_maillist', $_SESSION))
				// No; the CAS maillist is not set
				return false;
			elseif (is_array($maillist) && !in_array($_SESSION["cas_maillist"], $maillist))
				// No; CAS did not identify the user with any of the maillists in the array
				return false;
			elseif (!is_array($maillist) && $_SESSION["cas_maillist"] != $maillist)
				// No; CAS did not identify the user with this maillist
				return false;
                }
		return (array_key_exists('cas_logged_in', $_SESSION) && ($_SESSION["cas_logged_in"] === true));
	}

	/**
	 * requireLogin: Require a CAS-authenticated login session.
	 *
	 * This static function is called from the first few lines of any script wishing
	 * to secure access via CAS.
	 *
	 * @param string $maillist A mailing list the user *must* be a member of. Optional.
	 * @return True on success; redirects to CAS on failure.
	 */
	public static function requirelogin($maillist = false, $return_url = false) {

		// Make sure the session has been started
		if (session_status() == PHP_SESSION_NONE) session_start();
		if (session_status() == PHP_SESSION_DISABLED) throw new Exception("CAS requires session support in PHP, please enable it.");

		// Set the mailing list requirement.
		self::setMaillistRequirement($maillist);

		// Set the page to return to (also used as the "service" value).
		// Default of the current URL is used otherwise.
		if ($return_url || !array_key_exists('cas_return_url', $_SESSION) || !$_SESSION["cas_return_url"])
			self::setReturnURL($return_url);

		// Is the user presently logged in to a session?
		if (self::checkLoginStatus($maillist)) {

			// Is there a maillist requirement?
			if ($maillist) {

				// Yes. Do they meet the maillist requirement?
				if (is_array($maillist) && in_array($_SESSION['cas_maillist'], $maillist)) {
					// Yes. User is on at least one of the maillists
					return true;
				}
				elseif ($_SESSION["cas_maillist"] == $maillist) {
					// Yes. User is valid.
					return true;
				}
				else {
					// No. Redirect to CAS login.
					self::redirectToLogin();
				}
			}
			else {
				// No maillist requirement. User is valid.
				return true;
			}
		}

		// No. Well, does the user have a ticket?
		else if (self::checkTicketParam()) {

			// OK, go check the ticket.
			$authinfo = self::checkTicket();

			if ($authinfo) {
				self::userLogin($authinfo);
				return true;
			}
		}


        // No, the user is not logged in
		self::redirectToLogin();
	}

	/**
	 * isValidMaillist: checks maillist name validity.
	 * @param string $maillist to check
	 * @return boolean true if valid, false otherwise
	 */
	static function isValidMaillist($maillist) {
		if (is_array($maillist)) {
			foreach ($maillist as $single) {
				if (!((strlen($single) > 0) || !(preg_match('/^[a-zA-Z0-9\-]+$/', $single))))
					return false;
			}
			return true;
		}

		return ((strlen($maillist) > 0) && (preg_match('/^[a-zA-Z0-9\-]+$/', $maillist)));
	}

	/**
	 * setMaillistRequirement: Sets a requirement for a mailing list into the session.
	 * @param string $maillist The mailing list to require.
	 */
	private static function setMaillistRequirement($maillist = false) {
		// No maillist?
		if (!$maillist) $_SESSION["cas_maillist_requirement"] = false;

		// Invalid maillist?
		else if (!self::isValidMaillist($maillist)) throw new Exception("Invalid maillist name!");

		// Valid array of maillists.
		elseif (is_array($maillist))
			$_SESSION['cas_maillist_requirement'] = implode(',', $maillist);

		// Valid maillist.
		else $_SESSION["cas_maillist_requirement"] = $maillist;
	}


	/**
	 * checkTicketParam: see if a ticket has been supplied to the current page
	 * (which is user.php in most cases)
	 */
	static function checkTicketParam() {
		return (array_key_exists("ticket", $_GET) && (strlen($_GET["ticket"]) > 0));
	}

	/**
	 * checkTicket: checks the supplied ticket against the CAS server.
	 *
	 * @return CASAuthInfo object - Auth information about the user that logged in.
	 */
	static function checkTicket() {

		if (!self::checkTicketParam()) throw new Exception("No ticket supplied!");
		else $ticket = $_GET["ticket"];

		// Assemble the validation URL
		$validurl = self::casValidationURL($ticket, $_SESSION["cas_maillist_requirement"]);

		// Open the HTTPS connection and read the XML back.
		$doc = new \DOMDocument();
		$doc->load($validurl);

		// Check for failure
		if ($doc->getElementsByTagName("authenticationFailure")->length > 0) return false;

		// Parse the return for a successful result.  Bad/failed ticket if not found
		$success = $doc->getElementsByTagName("authenticationSuccess");
		if ($success->length < 1) return false;

		// Get username from success.  Bad/failed ticket if username not found or invalid
		$username = $success->item(0)->getElementsByTagName("user")->item(0)->nodeValue;
		if (strlen($username) < 1) return false;

		// Get authtype and maillist from success
		$authtype = $success->item(0)->getElementsByTagName("authtype")->item(0)->nodeValue;
		$maillist = $success->item(0)->getElementsByTagName("maillist")->item(0)->nodeValue;
		if (strlen($maillist) < 1) $maillist = false;

		// Create and return a UserInfo object with details from CAS
		return new CASAuthInfo($username, $authtype, $maillist);
	}

	/**
	 * userLogin: log the user in. Assumes ticket has been checked, etc.
	 * @param CASAuthInfo $authinfo - identifies the of the user to log in.
	 */
	public static function userLogin($authinfo) {

		// Assign session variables.
		$_SESSION['cas_username'] = $authinfo->getUsername();
		$_SESSION["cas_email"] = $authinfo->getUsername() . '@' . CASOptions::EmailDomain();
		$_SESSION['cas_logged_in'] = true;
		$_SESSION['cas_authtype'] = $authinfo->getAuthType();
		if ($authinfo->getMailList() !== false)
			$_SESSION['cas_maillist'] = $authinfo->getMailList();

		return true;
	}

	/**
	 * userLogin: log the user out (from this application, not CAS).
	 */
	public static function userLogout() {

		// Clear session variables.
		unset($_SESSION['cas_username']);
		unset($_SESSION["cas_email"]);
		unset($_SESSION['cas_logged_in']);
		unset($_SESSION['cas_authtype']);
		unset($_SESSION['cas_maillist']);

		return true;
	}

	/**
	 * URL Generators
	 */


	/**
	 * setReturnURL: Sets the "service" value, aka. the "return page" that we will jump
	 * back to once authentication is complete.
	 * @param string $service The full URL to return to.
	 */
	private static function setReturnURL($return) {
		if($return) $_SESSION["cas_return_url"] = $return;
		else $_SESSION["cas_return_url"] = self::getCurrentURL();
	}

	// Get that return URL, or the default one.
	private static function getReturnURL() {
		if (strlen($_SESSION["cas_return_url"]) > 0) return $_SESSION["cas_return_url"];
		else throw new Exception("No return URL saved in session!");
	}

	// Get the default return URL.
	private static function getCurrentURL() {
		$protocol = (array_key_exists('https', $_SERVER) && $_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		return $protocol.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
	}

	// The CAS login page
	private static function casLoginURL($maillist = false) {
		$url = CASOptions::LoginURL() . '?service='.urlencode(self::getReturnURL());
		// Is maillist specified?
		if ($maillist !== false) {
			// Yes; is it a list of maillists?
			if (strpos($maillist, ','))
				// Yes; add the list as a requirement
				$url .= '&allow=!'.str_replace(',',',!', $maillist);
			else
				// No; add a single maillist as the requirement
				$url .= '&allow=!'.$maillist;
		}
		return $url;
	}

	// The CAS logout page
	private static function casLogoutURL($app_description = false) {
		 $url = CASOptions::LogoutURL();
		 if ($app_description) $url .= '?app='.urlencode($app_description);
		 return $url;
	}

	// The CAS validation page
	private static function casValidationURL($ticket, $maillist = false) {

		$url  = CASOptions::ValidateURL();
		$url .= "?service=".urlencode(self::getReturnURL());
		$url .= "&ticket=".$ticket;

		if (strpos($maillist, ','))
			$url .= '&allow=!' . str_replace(',', ',!', $maillist);
		elseif ($maillist !== false) $url .= '&allow=!'.$maillist;
		return $url;
	}

	/**
	 * Redirectors
	 */

	// redirectToLogin sends the user to the CAS login page including maillist reqs
	public static function redirectToLogin() {
		$url = self::casLoginURL($_SESSION["cas_maillist_requirement"]);
		die(header("Location: $url"));
	}

	// redirectToLogout sends them to the SFU service logout page.
	public static function redirectToLogout() {
		$url = self::casLogoutURL(APPTITLE);
		die(header("Location: $url"));
	}
}

?>
