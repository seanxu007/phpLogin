<?php
//edit user

include_once 'db_connect.php';
include_once 'psl-config.php';
include_once 'functions.php';

$error_msg = "";



if (isset($_POST['username'], $_POST['firstname'], $_POST['lastname'], $_POST['active'], $_GET["id"])) {
    // Sanitize and validate the data passed in
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
    $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
    $active = filter_input(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $id = $_GET["id"];

    //Check active validation
    if ($active != "Y") {
        $active = "N";
    }
    //Check id exiting or not
    $prep_stmt = "SELECT tmp.user.email FROM tmp.user WHERE tmp.user.id = ? LIMIT 1";
    $stmt = $mysqli->prepare($prep_stmt);

    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            // A user with this id not exists
            $error_msg .= '<p class="error">This user is not existing.</p>';
        }
    } else {
        $error_msg .= '<p class="error">Database error</p>';
    }

    // update user 
    if ($insert_stmt = $mysqli->prepare("UPDATE tmp.user set tmp.user.username ='$username', tmp.user.first_name='$firstname', tmp.user.last_name='$lastname', tmp.user.active='$active', tmp.user.update_date='".date("Y-m-d H:i:s")."' where tmp.user.id=?")) {
        $insert_stmt->bind_param('i', $id);
        // Execute the prepared query.
        if (! $insert_stmt->execute()) {
            header('Location: ../error.php?err=Registration failure: UPDATE');
            exit();
        }
    }
    header('Location: ../protected_page.php');
    exit();

}