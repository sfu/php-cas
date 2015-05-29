<?php namespace SFU;

/**
 * SFU CAS library for PHP: Options class
 *
 * Modify this file for your environment.
 *
 * @author Mike Sollanych, Ross Cawston
 * @package sfu/php-cas
 */


class CASOptions {

	// CAS server name
	static function ServerName()		{ return "cas.sfu.ca"; }

	// Port to use for HTTPS communication
	static function ServerPort()		{ return 443; }

	// Directory to the CAS scripts on the server
 	//static function ServerDirectory()   { return "/cgi-bin/WebObjects/cas.woa/wa/"; }
	static function ServerDirectory()   { return "/cas/"; }

	// Domain to assume for CAS logons
	static function EmailDomain()		{ return 'sfu.ca'; }

	// Base CAS URL
	static function URL()               { return 'https://' . self::ServerName() . self::ServerDirectory(); }

	// Names of each of the scripts on the CAS server
	static function LoginURL() 	        { return self::URL() . "login"; }
	static function LogoutURL()         { return self::URL() . "appLogout"; }
	static function ValidateURL()		{ return self::URL() . "serviceValidate"; }

}