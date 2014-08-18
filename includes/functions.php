<?php
//basic function called in other place

include_once 'psl-config.php';
include_once 'module/PHPMailer-master/class.phpmailer.php';

function sec_session_start() {
    $session_name = 'sec_session_id';   // Set a custom session name 
    $secure = SECURE;

    // This stops JavaScript being able to access the session id.
    $httponly = true;

    // Forces sessions to only use cookies.
    if (ini_set('session.use_only_cookies', 1) === FALSE) {
        header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
        exit();
    }

    // Gets current cookies params.
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);

    // Sets the session name to the one set above.
    session_name($session_name);

    session_start();            // Start the PHP session 
    session_regenerate_id();    // regenerated the session, delete the old one. 
}

function login($email, $password, $mysqli) {
    // Using prepared statements means that SQL injection is not possible. 
    if ($stmt = $mysqli->prepare("SELECT tmp.user.id, tmp.user.username, tmp.user.password, tmp.user.salt, tmp.user.group 
				  FROM tmp.user 
                                  WHERE tmp.user.email = ? LIMIT 1")) {
        $stmt->bind_param('s', $email);  // Bind "$email" to parameter.
        $stmt->execute();    // Execute the prepared query.
        $stmt->store_result();

        // get variables from result.
        $stmt->bind_result($user_id, $username, $db_password, $salt, $group);
        $stmt->fetch();

        // hash the password with the unique salt.
        $password = hash('sha512', $password . $salt);
        if ($stmt->num_rows == 1) {
            // If the user exists we check if the account is locked
            if (checkbrute($user_id, $mysqli) == true) {
                // Account is locked 
                return false;
            } else {
                // Check if the password in the database matches 
                if ($db_password == $password) {
                    // Password is correct!
                    $user_browser = $_SERVER['HTTP_USER_AGENT'];

                    // XSS protection as we might print this value
                    $user_id = preg_replace("/[^0-9]+/", "", $user_id);
                    $_SESSION['user_id'] = $user_id;

                    $username = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $username);

                    $_SESSION['username'] = $username;
                    $_SESSION['login_string'] = hash('sha512', $password . $user_browser);
                    if ($group == "admin") {
                        $_SESSION['isadmin'] = true;
                    } else {
                        $_SESSION['isadmin'] = false;
                    }

                    // Login successful. 
                    return true;
                } else {
                    // Password is not correct, record this attempt
                    $now = time();
                    if (!$mysqli->query("INSERT INTO tmp.login_attempts(tmp.login_attempts.user_id, tmp.login_attempts.time) 
                                    VALUES ('$user_id', '$now')")) {
                        header("Location: ../error.php?err=Database error: login_attempts");
                        exit();
                    }

                    return false;
                }
            }
        } else {
            // No user exists. 
            return false;
        }
    } else {
        // Could not create a prepared statement
        header("Location: ../error.php?err=Database error: cannot prepare statement");
        exit();
    }
}

function checkbrute($user_id, $mysqli) {
    // Get timestamp of current time 
    $now = time();

    // All login attempts are counted from the past 2 hours. 
    $valid_attempts = $now - (2 * 60 * 60);

    if ($stmt = $mysqli->prepare("SELECT tmp.login_attempts.time 
                                  FROM tmp.login_attempts 
                                  WHERE tmp.login_attempts.user_id = ? AND tmp.login_attempts.time > '$valid_attempts'")) {
        $stmt->bind_param('i', $user_id);

        // Execute the prepared query. 
        $stmt->execute();
        $stmt->store_result();

        // If there have been more than 5 failed logins 
        if ($stmt->num_rows > 5) {
            return true;
        } else {
            return false;
        }
    } else {
        // Could not create a prepared statement
        header("Location: ../error.php?err=Database error: cannot prepare statement");
        exit();
    }
}

function login_check($mysqli) {    
    // Check if all session variables are set 
    error_log('33'.$_SESSION['user_id']);
    if (isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['login_string'])) {
        $user_id = $_SESSION['user_id'];
        $login_string = $_SESSION['login_string'];
        $username = $_SESSION['username'];

        $user_browser = $_SERVER['HTTP_USER_AGENT'];
        
        if ($stmt = $mysqli->prepare("SELECT tmp.user.password 
				      FROM tmp.user 
				      WHERE tmp.user.id = ? LIMIT 1")) {
            // Bind "$user_id" to parameter. 
            $stmt->bind_param('i', $user_id);
            $stmt->execute();   // Execute the prepared query.
            $stmt->store_result();
            
            if ($stmt->num_rows == 1) {
                // If the user exists get variables from result.
                $stmt->bind_result($password);
                $stmt->fetch();
                $login_check = hash('sha512', $password . $user_browser);

                if ($login_check == $login_string) {
                    // Logged In!!!! 
                    return true;
                } else {
                    // Not logged in 
                    return false;
                }
            } else {
                // Not logged in 
                return false;
            }
        } else {
            // Could not prepare statement
            header("Location: ../error.php?err=Database error: cannot prepare statement");
            exit();
        }
    } else {
        // Not logged in 
        return false;
    }
}

function esc_url($url) {

    if ('' == $url) {
        return $url;
    }

    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
    
    $strip = array('%0d', '%0a', '%0D', '%0A');
    $url = (string) $url;
    
    $count = 1;
    while ($count) {
        $url = str_replace($strip, '', $url, $count);
    }
    
    $url = str_replace(';//', '://', $url);

    $url = htmlentities($url);
    
    $url = str_replace('&amp;', '&#038;', $url);
    $url = str_replace("'", '&#039;', $url);

    if ($url[0] !== '/') {
        // We're only interested in relative links from $_SERVER['PHP_SELF']
        return '';
    } else {
        return $url;
    }
}

function sendWelcomeEmail($username,$firstname, $lastname, $email) {
    //Send welcome email when register finish
    $mail = new PHPMailer(); // create a new object
    $mail->IsSMTP(); // enable SMTP
    $mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
    $mail->SMTPAuth = true; // authentication enabled
    $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 465; // or 587
    $mail->IsHTML(true);
    $mail->Username = "sean.xu.test@gmail.com";
    $mail->Password = "testsite";
    $mail->SetFrom("sean.xu.test@gmail.com");
    $mail->Subject = "Welcome to testsite";
    $mail->Body = "Dear $firstname $lastname, welcome to www.testsite.com \nYou have registerd as $username";
    $mail->AddAddress($email);
    if(!$mail->Send())
    {
      echo "Mailer Error: " . $mail->ErrorInfo;
    }
    else
    {
      echo "Message sent!";
    }
}

function get_user($mysqli, $id = null) {
    //get user list
    if ($id == null) {
        $sql = "SELECT tmp.user.id, tmp.user.username, tmp.user.first_name, tmp.user.last_name, tmp.user.email, tmp.user.active, tmp.user.identifier, tmp.user.create_date, tmp.user.salt 
				  FROM tmp.user order by tmp.user.first_name";
    } else {
        $sql = "SELECT tmp.user.id, tmp.user.username, tmp.user.first_name, tmp.user.last_name, tmp.user.email, tmp.user.active, tmp.user.identifier, tmp.user.create_date 
				  FROM tmp.user where tmp.user.id =".$id;
    }
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->execute();    // Execute the prepared query.
        $stmt->store_result();

        return $stmt;
    } else {
        // Could not create a prepared statement
        header("Location: ../error.php?err=Database error: cannot prepare statement");
        exit();
    }
}

function social_login($user_profile,$provider,$mysqli) {
    //Grab parameter that need to use in mysql database
    $firstname_social=$user_profile->firstName;
    $lastName_social=$user_profile->lastName;
    $email_social=$user_profile->email;
    $identifier_social=$user_profile->identifier;
    $username_social=$user_profile->displayName;
    $user_existing = false;
    $user_browser = $_SERVER['HTTP_USER_AGENT'];
    //Check email existing or not
    $prep_stmt = "SELECT tmp.user.id,tmp.user.username,tmp.user.password,tmp.user.group FROM tmp.user WHERE tmp.user.identifier = ? LIMIT 1";
    $stmt = $mysqli->prepare($prep_stmt);
    
    if ($stmt) {
        $stmt->bind_param('s', $identifier_social);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            // A user with this email address already exists 
            // Login with this user
            $stmt->bind_result($user_id,$username,$password,$group);
            $stmt->fetch();
            $user_existing = true;
            

            // Set sessions
            $user_id = preg_replace("/[^0-9]+/", "", $user_id);
            $_SESSION['user_id'] = $user_id;

            $username = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $username);

            $_SESSION['username'] = $username;
            $_SESSION['login_string'] = hash('sha512', $password . $user_browser);
            if ($group == "admin") {
                $_SESSION['isadmin'] = true;
            } else {
                $_SESSION['isadmin'] = false;
            }
        }
    } else {
        $error_msg .= '<p class="error">Database error</p>';
    }
    
    // No user existing in database, create one
    if (empty($error_msg) && $user_existing == false) {
        // Create a random salt
        $random_salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), TRUE));

        // Create salted password 
        $password_social = hash('sha512', "blankblankblank" . $random_salt);

        // Insert the new user into the database 
        if ($insert_stmt = $mysqli->prepare("INSERT INTO tmp.user (tmp.user.login_provider,tmp.user.identifier,tmp.user.first_name, tmp.user.last_name, tmp.user.email,tmp.user.create_date,tmp.user.update_date,tmp.user.username, tmp.user.password, tmp.user.salt) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('ssssssssss', $provider, $identifier_social, $firstname_social, $lastName_social, $email_social,date("Y-m-d H:i:s"), date("Y-m-d H:i:s"),$username_social, $password_social, $random_salt);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
                header('Location: ../error.php?err=Registration failure: INSERT');
                exit();
            }
            // Set sessions
            $prep_stmt = "SELECT tmp.user.id FROM tmp.user WHERE tmp.user.identifier = ? LIMIT 1";
            $stmt = $mysqli->prepare($prep_stmt);
            $stmt->bind_param('s', $identifier_social);
            $stmt->execute();
            $stmt->bind_result($user_id_social);
            $stmt->fetch();
            $_SESSION['user_id'] = $user_id_social;
            $_SESSION['username'] = $username_social;
            $_SESSION['login_string'] = hash('sha512', $password_social . $user_browser);
            $_SESSION['isadmin'] = false;
        }
    }
}
