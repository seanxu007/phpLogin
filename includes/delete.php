<?php
//delete user 

include_once 'db_connect.php';
include_once 'psl-config.php';
include_once 'functions.php';

$error_msg = "";



if (isset($_POST['id'], $_POST['salt'])) {
    // Sanitize and validate the data passed in
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
    $salt = filter_input(INPUT_POST, 'salt', FILTER_SANITIZE_STRING);
    
    //Check id exiting or not
    $prep_stmt = "SELECT tmp.user.email FROM tmp.user WHERE tmp.user.id = ? and tmp.user.salt = ? LIMIT 1";
    $stmt = $mysqli->prepare($prep_stmt);

    if ($stmt) {
        $stmt->bind_param('is', $id, $salt);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            // A user with this id not exists
            $error_msg .= '<p class="error">This user is not existing.</p>';
        }
    } else {
        $error_msg .= '<p class="error">Database error</p>';
    }

    // delete user 
    if ($insert_stmt = $mysqli->prepare("DELETE from tmp.user where tmp.user.id = ? and tmp.user.salt = ?")) {
        $insert_stmt->bind_param('is', $id, $salt);
        // Execute the prepared query.
        if (! $insert_stmt->execute()) {
            header('Location: ../error.php?err=Registration failure: UPDATE');
            exit();
        }
    }
    header('Location: ../protected_page.php');
    exit();

}