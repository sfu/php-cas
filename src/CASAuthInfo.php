<?php namespace SFU;

/**
 * SFU CAS library for PHP: Authentication Information class
 *
 * @author Mike Sollanych, Ross Cawston
 * @package sfu/php-cas
 */

class CASAuthInfo {
	// The Username returned by CAS
	private $username;
	// The AuthType used by CAS to identify the user (e.g. "SFU")
	private $authtype;
	// The MailList used by CAS to identify the user (can be false)
	private $maillist;

	function __construct($username, $authtype, $maillist = false) {
		$this->username = $username;
		$this->authtype = $authtype;
		$this->maillist = $maillist;
	}

	public function getUsername() { return $this->username; }
	public function getAuthType() { return $this->authtype; }
	public function getMailList() { return $this->maillist; }
}