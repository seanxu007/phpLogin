<?php

/* 
 * Copyright (C) 2013 peter
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

include_once 'db_connect.php';
include_once 'psl-config.php';
include_once 'functions.php';

$error_msg = "";



if (isset($_POST['id'], $_POST['salt'])) {
    // Sanitize and validate the data passed in
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
    $salt = $_POST['salt'];
    
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

    // update user 
    if ($insert_stmt = $mysqli->prepare("DELETE from tmp.user where tmp.user.id = ? and tmp.user.salt = ?")) {
        $insert_stmt->bind_param('is', $id, $salt);
        //, "user", "N", date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), "null", "email", 
        // Execute the prepared query.
        if (! $insert_stmt->execute()) {
            header('Location: ../error.php?err=Registration failure: UPDATE');
            exit();
        }
    }
    header('Location: ../protected_page.php');
    exit();

}