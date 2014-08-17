<?php
//Get user list api call
header('Content-type: application/json');
include_once '../includes/db_connect.php';
    
if (isset($_POST['token'])) {
    // Token validation
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_STRING);
    $now = time();
    $sql = "SELECT tmp.token.id from tmp.token where tmp.token.token='$token' and tmp.token.time>$now";
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows >0) {
    // Send user list back
    $sql = "SELECT tmp.user.username, tmp.user.first_name, tmp.user.last_name, tmp.user.email, tmp.user.active, tmp.user.identifier, tmp.user.create_date, tmp.user.login_provider 
                              FROM tmp.user order by tmp.user.first_name";

        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->execute();    // Execute the prepared query.
            $stmt->bind_result($username, $firstname, $lastname, $email, $active, $identifier, $createdate, $loginprovider);
            // Generate result as a json format
            $data = array();
            $i =0;
            while($stmt->fetch()){
                $data[$i]['username'] = $username;
                $data[$i]['firstname'] = $firstname;
                $data[$i]['lastname'] = $lastname;
                $data[$i]['email'] = $email;
                $data[$i]['active'] = $active;
                $data[$i]['identifier'] = $identifier;
                $data[$i]['createdate'] = $createdate;
                $data[$i]['loginprovider'] = $loginprovider;
                $i++;
            }
            echo json_encode($data);
        } else {
            // Could not create a prepared statement
            echo "{message:'No user finded'}";
        }
    } else {
        echo "{message: 'Wront post'}";
    }
} else {
    echo "{message:'Wrong post'}";
}

