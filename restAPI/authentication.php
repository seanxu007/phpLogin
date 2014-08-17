<?php
//api call authentication function, return token for other rest api service
header('Content-type: application/json');
include_once '../includes/db_connect.php';
    
if (isset($_POST['email'], $_POST['password'])) {
    // Check parameters validation
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $p = hash('sha512', $password);
    //Generate token and save to database
    $random_salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), TRUE));
    $time = time()+(24*60);
    $token = hash('sha512', "generateTokenAccordingToARandomSalt" . $salt); 
    $mysqli->query("INSERT INTO tmp.token(tmp.token.token, tmp.token.time) VALUES ('$token', '$time')"); 
    // Check database and send back authentication token
    $sql = "SELECT tmp.user.password, tmp.user.salt 
                              FROM tmp.user where tmp.user.email ='".$email."' LIMIT 1";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->execute();    // Execute the prepared query.
        $stmt->bind_result($passwordDB, $salt);
        $stmt->fetch();
        $password = hash('sha512',$p.$salt);
        if ($password == $passwordDB) {
            //Authentication success, send back token
            
            echo "{token:'$token',success:true,message:'Authenticate successfully'}";
        } else {
            //Authentication fail
            echo "{message:'Authenticate fail'}";
        }
    } else {
        // Could not create a prepared statement
        echo "{message:'Authenticate fail'}";
    }
    
}


