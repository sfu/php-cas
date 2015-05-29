# php-cas

php-cas is a library for Simon Fraser University's slightly-modified version of JASIG CAS, the Central Authentication System. It should work with standard CAS implementations, but has been customized for authorization support as well as the normal CAS authentication functionality.

Background
----------

Most websites at SFU that use CAS rely on the mod-auth-cas Apache module, which is available in a customized form for Apache 2.2 that also provides authorization support in the form of requirements for membership within a particular SFU mailing list.

Apache 2.2 is getting pretty long in the tooth, and the module doesn't work with other web servers, so we have created a purely PHP implementation of the CAS functionality that can be integrated with existing PHP applications with a minimal amount of work.

Installation
------------

**If you're using Composer:**

Just run:
> php composer.phar require sfu/php-cas

> php composer.phar update

> php composer.phar dump-autoload


and as long as you have set up the Composer autoloader correctly, you shouldn't have to do anything else.

**If you're not:**

Copy src/*.php into your application in an appropriate location, and add a require() call into the application's header files to ensure the needed files are loaded (

Configuration
-------------

Within CASOptions.php is the SFU\CASOptions class which you must edit for your environment. Defaults for the SFU environment are included, so for use at SFU no modification should be needed.

Options are defined as functions for versatility, and include:

 1. ServerName() - returns the name of the CAS server
 2. ServerPort() - returns the port to contact the CAS server on 
 3. ServerDirectory() - returns the toplevel path under which the CAS endpoints are found
 4. URL() - returns the base URL for each CAS endpoint, assembled from the above functions
 5. LoginURL() - relative path for the Login endpoint
 6. LogoutURL() - relative path for the Logout endpoint
 6. EmailDomain() - returns the domain name to append to usernames to create email addresses

Usage
-----

Usage is simple, just add the following line to the top of any entry point or header file that will be loaded by a web browser:

    SFU\CAS::requireLogin();

This will cause the CAS session to be checked, and if there's no currently valid session, the user will be redirected to the CAS server and then back to your app again. The second time around, there will be a GET parameter with ticket information that the CAS library will check against the CAS server, and if valid, it will create your session, logging you in.


**Mailing List Based Authorization**

Applicable to SFU CAS only. If you want to make it mandatory that a user be part of a particular maillist to get access, simply pass the maillist as the first parameter to requireLogin:

    SFU\CAS::requireLogin("maillist-name");

**Alternate Return URL**

If you don't want to send the user back to the current endpoint, provide a different one as the second parameter to requireLogin:

    SFU\CAS::requireLogin("maillist-name", "https://www.whatever.com/index.php");

**Logout**

To clear the session, call userLogout():

    SFU\CAS::userLogout();

To actually log the user out of CAS, follow up with a redirector call:

    SFU\CAS::redirectToLogout();

**Checking authentication without redirection**

In some contexts, you may not want to redirect the user when they aren't logged in, e.g. web-services style REST calls to your application. In this case, just call checkLoginStatus, optionally with a maillist parameter:

    $logged_in = SFU\CAS::checkLoginStatus();
    $logged_in_with_maillist = SFU\CAS::checkLoginStatus("maillist-name");

and you will receive a boolean that you can then handle yourself to return acceptable error output to your application.

Testing
-------

Unit tests have not been created yet. In the meantime, you can do a quick test using the PHP development web server.
From the tests/ folder, run:

	php -S localhost:8000
	
and then fire up your web browser to http://localhost:8000/tests.php and you should be presented with your CAS login screen.
