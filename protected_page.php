<?php
//Show user list page

include_once 'includes/db_connect.php';
include_once 'includes/functions.php';

sec_session_start();
$stmt = get_user($mysqli);
$stmt->bind_result($id, $username, $firstname, $lastname, $email, $active, $identifier, $createdate, $salt);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Secure Login: Protected Page</title>
        <link rel="stylesheet" href="styles/main.css" />
    </head>
    <body>
        <?php if (login_check($mysqli) == true) : ?>
            <p>Welcome <?php echo htmlentities($_SESSION['username']); ?>!</p>
            <table style="width:600px">
                <tr>
                    <td>First Name</td>
                    <td>Last Name</td> 
                    <td>Email</td>
                    <td>Active</td>
                    <td>Facebook ID</td>
                    <td>Create Date</td>
                    <td></td>
                    <td></td>
                </tr>
                <?php while ($stmt->fetch()) : ?>
                <tr>
                    <td><?php echo $firstname; ?></td>
                    <td><?php echo $lastname; ?></td> 
                    <td><?php echo $email; ?></td>
                    <td><?php echo $active; ?></td>
                    <td><?php echo $identifier; ?></td> 
                    <td><?php echo $createdate; ?></td>
                    <?php if ($_SESSION['isadmin'] || $_SESSION['user_id'] == $id) : ?>
                    <td><input type="button" 
                               name="edit" id="eidt" value="eidt"  
                               onclick="window.location='edit.php?id=<?php echo $id ?>'" /></td>
                    <?php if (login_check($mysqli) == true) : ?>
                    <td><form method="POST" action="includes/delete.php" onsubmit="return confirm('Are you sure you want to delete this user?');">
                        <input type="hidden" name="id" value="<?php echo $id; ?>" />
                        <input type="hidden" name="salt" value="<?php echo $salt; ?>" />
                        <button type="submit">Delete</button>
                    </form></td>
                    <?php endif; ?>
                    <?php endif; ?>
                </tr>
                <?php endwhile;?>
            </table>
            <p>Return to <a href="index.php">login page</a></p>
        <?php else : ?>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="index.php">login</a>.
            </p>
        <?php endif; ?>
    </body>
</html>
