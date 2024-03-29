<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

// ----------------------------------------------------------------------------------------
//	HybridAuth Config file: http://hybridauth.sourceforge.net/userguide/Configuration.html
// ----------------------------------------------------------------------------------------

$config = array("base_url" => "http://www.testsite.com/includes/module/hybridauth-master/hybridauth", 
        "providers" => array ( 
 
            "Facebook" => array ( 
                "enabled" => true,
                "keys"    => array ( "id" => "key", "secret" => "secret" ),
                "scope" => "email, user_about_me, user_birthday, user_hometown"  //optional.              
            ),
 
            "Twitter" => array ( 
                "enabled" => true,
                "keys"    => array ( "key" => "key", "secret" => "secret" ) 
            ),
        ),
        // if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
        "debug_mode" => false,
        "debug_file" => "debug.log",
    );
return $config;
