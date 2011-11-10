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

	function statusRow($row) {
		global $db, $i, $jsend;
	   	if ($row['status'] == "0") {
			echo '<tr style="text-align: center" class="offline">';
		} elseif ($row['uptime'] == "n/a") {
			echo '<tr style="text-align: center" class="online-but-no-data">';
		} else {
			echo '<tr style="text-align: center">';
		}
		$dbq = $db->prepare('SELECT COUNT(*) FROM processes WHERE uid = ?');
		$dbr = $dbq->execute(array($row['uid']));
		$servicecount = $dbq->fetch();
		$numrows = 1;
		if ($row['note'] != "") {
			$numrows++;
		}
		if ($servicecount[0] > 0) {
			$numrows++;
		}
		echo '<td rowspan="'.$numrows.'"><a href="/history.php?uid='. $row['uid'].'">'. $row['hostname'] .'</a></td>';

		echo '<td><span id="time-'.$i.'"></span></td>';
		$jsend .= '$(function () {
                                $(\'#time-'.$i.'\').countdown({since: "-'.(time()-$row['time']).'S", compact: true});
                        });';

		echo '<td>'. $row['uptime'] .'</td>';
		echo '<td class="5pad">';
		if(empty($row['mtotal'])) {
			echo "N/A";
		} else {
			$mp = ($row['mused'])/$row['mtotal']*100;
			$used = $row['mused'];
			echo '<div class="progress-container"><div class="progress-container-percent" style="width:'. $mp .'%"><div class="bartext">'. $used .'/'. $row['mtotal'] .'MB</div></div></div></td>';
		}
		echo '</td>';
		echo '<td class="5pad">';
		if(isset($row['diskused'])) {
			$mp = ($row['diskused']/$row['disktotal'])*100;
			echo '<div class="progress-container"><div class="progress-container-percent" style="width:'. $mp .'%"><div class="bartext">'. format_kbytes($row['diskused']) .'/'. format_kbytes($row['disktotal']) .'GB</div></div></div>';
		} else {
			echo 'N/A';
		}
		echo '</td>';
		echo '<td class="5pad">';
		echo '<span class="loadavg" style="background-color: #'.gen_color($row['load1']).'">'. sprintf('%.02f', $row['load1']) .'</span>&nbsp;';
		echo '<span class="loadavg" style="background-color: #'.gen_color($row['load5']).'">'. sprintf('%.02f', $row['load5']) .'</span>&nbsp;';
		echo '<span class="loadavg" style="background-color: #'.gen_color($row['load15']).'">'. sprintf('%.02f', $row['load15']) .'</span>&nbsp;';
		echo '</td>';
		echo '</tr>';

		if ($servicecount[0] > 0) {
		$dbq = $db->prepare('SELECT * FROM processes WHERE uid = ? ORDER BY name ASC');
		$dbr = $dbq->execute(array($row['uid']));
		echo '<tr>';
		echo '<td colspan="5" style="text-align: left; line-height: 22px;"><strong>Services:</strong><ul class="services">';
		while ($service = $dbq->fetch(PDO::FETCH_ASSOC)) {
			switch ($service['status']) {
				case 0:
					$class = "service-up";
					break;
				case 1:
					$class = "service-warning";
					break;
				case 2:
					$class = "service-critical";
					break;
				case -1:
					$class = "service-unknown";
					break;
			}
			echo '<li class="'.$class.'">'. $service['name'] .'</li>';
		}
		echo '</ul>';
		echo '</td>';
		echo '</tr>';
		}
		if ($row['note'] != "") {
			echo '<tr><td colspan="5" style="text-align:left;"><strong>Notes: </strong>'.$row['note'].'</td></tr>';
		}
		$i++;
	}

		/* From http://www.php.net/manual/en/function.filesize.php#100097, removed bytes*/
	function format_kbytes($size) {
		return round($size/1024/1024, 2);
	}

	function gen_color($load) {
		$green = 0;
		$red = 3;
		$colors = array('00FF00', '11FF00', '22FF00', '33FF00', '44FF00', '55FF00', '66FF00', '77FF00', '88FF00', '99FF00', 'AAFF00', 'BBFF00', 'CCFF00', 'DDFF00', 'EEFF00', 'FFFF00', 'FFEE00', 'FFDD00', 'FFCC00', 'FFBB00', 'FFAA00', 'FF9900', 'FF8800', 'FF7700', 'FF6600', 'FF5500', 'FF4400', 'FF3300', 'FF2200', 'FF1100', 'FF0000');
		$count = count($colors)-1;
		$map = intval((($load - $green) * $count) / ($red - $green));
		if($map > $count) { $map = $count; }
		return $colors[$map];
	}

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
