<?php
// User edit page

include_once 'includes/db_connect.php';
include_once 'includes/functions.php';
sec_session_start();
$stmt = get_user($mysqli,$_GET["id"]);
$stmt->bind_result($id, $username, $firstname, $lastname, $email, $active, $identifier, $createdate);
$stmt->fetch();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>User Edit Form</title>
        <script type="text/JavaScript" src="js/forms.js"></script>
        <link rel="stylesheet" href="styles/main.css" />
    </head>
    <body>
        <?php if (login_check($mysqli) == true) : ?>
        <form method="post" name="registration_form" action="includes/edit.inc.php?id=<?php echo $id; ?>">
            Username: <input type='text' name='username' id='username' value="<?php echo $username ?>" /><br>
            first name: <input type='text' name='firstname' id='firstname' value="<?php echo $firstname ?>" /><br>
            last name: <input type='text' name='lastname' id='lastname' value="<?php echo $lastname ?>" /><br>
            active: <input type="text" name="active" id="active" value="<?php echo $active ?>" />(Y or N)<br>
            <input type="button" 
                   value="submit" 
                   onclick="return editform(this.form,
                                   this.form.username,
                                   this.form.firstname,
                                   this.form.lastname,
                                   this.form.active);" /> 
        </form>
        <p>Return to the <a href="index.php">login page</a>.</p>
        <?php else : ?>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="index.php">login</a>.
            </p>
        <?php endif; ?>
    </body>
</html>
