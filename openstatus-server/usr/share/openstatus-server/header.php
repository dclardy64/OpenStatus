<?php
        require('config.php');

	// Check if the database file has the proper permissions
        $processUser = posix_geteuid();
        $processUserInfo = posix_getpwuid($processUser);
        $dbUser = fileowner($db);
        $dbUserInfo = posix_getpwuid($dbUser);
        if ($dbUser != $processUser) {
                die('Database '.$db.' is not owned by the same user this script is running as.  Please make sure that the database file and its parent directory are owned by the user this script runs as.<br /><br />Database user: '.$dbUserInfo['name'].'<br />'.$processUserInfo['name']);
        }
        $start = microtime();
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
	if ($_SERVER['SCRIPT_NAME'] == '/index.php' || $_SERVER['SCRIPT_NAME'] == '/history.php') {
		header('Refresh: 60');
	}
        header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html>
        <head>
                <meta charset="utf-8">
                <title>OpenStatus - Server statistics</title>
                <link rel="stylesheet" href="/css/style.css"></link>
                <script type="text/javascript" src="/js/jquery-1.6.4.min.js"></script>
                <script tyle="text/javascript" src="/js/jquery.countdown.min.js"></script>
        </head>
	<body>
                <div id="wrapper">
                        <h1>Server statistics</h1>
                        <ul id="menu">
                                <li><a href="index.php">View Status</a></li>
                                <li><a href="admin.php">Admin</a></li>
<?php

if (isset($_GET['logout'])) {
	setcookie('status-auth', '#');
	$auth = false;
	echo '</ul><p>You have been logged out.</p>';
	require('footer.php');
	die();
}


if (isset($_COOKIE['status-auth'])) {
	$token = md5($username.$password);
	if ($_COOKIE['status-auth'] === $token) {
		$auth = true;
	} else {
		$auth = false;
	}
} else {
	$auth = false;
}

if (isset($_POST['login'])) {
        $token = md5($username.$password);
	if (md5($_POST['username'].$_POST['password']) !== $token) {
		echo '</ul><p>Invalid username or password.</p>';
		$auth = false;
	} else {
		setcookie('status-auth', $token);
		$auth = true;
	}
}

if ($auth === true) {
	echo '<li><a href="admin.php?logout">Log Out</a></li>';
	echo '</ul>';
} else { 
	echo '</ul>';
}


if ($auth === false && $requirelogin === true) {
	echo '
			<form action="admin.php" method="post">
				<table>
					<tr><th colspan="2">Please Log In</th></tr>
					<tr>
						<th>Username</th>
						<td><input type="text" name="username" /></td>
					</tr>
					<tr>
						<th>Password</th>
						<td><input type="password" name="password" /></td>
					</tr>
					<tr><th colspan="2"><input type="submit" name="login" value="Log In" /></th></tr>
				</table>
			</form>';
} else {

        try {
                $db = new PDO('sqlite:'. $db);
        } catch (PDOException $e) {
                error_log($_SERVER['SCRIPT_FILENAME'] .' - Unable to connect to the database: '. $e);
                die('Unable to connect to the database - please try again later.');
        }
}

?>
