<?php

	if (file_exists('/etc/openstatus/config.php') !== TRUE) {
		die('Config file /etc/openstatus/config.php not found!');
	}
        require('/etc/openstatus/config.php');

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
        header('Pragma: no-cache');

	function statusRow($row) {
		global $db, $i, $jsend, $lbjs, $provider;

		if ($row['provider'] != $provider) {
			echo '<tr><td colspan="7" style="text-align: left; vertical-align: middle; font-weight: bold; font-size: 10px; padding-left: 5px;">'. $row['provider'] .'</td></tr>';
			$provider = $row['provider'];
		}

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
		echo '<td rowspan="'.$numrows.'"><a href="history.php?uid='. $row['uid'].'">'. $row['hostname'] .'</a></td>';

		echo '<td><span id="time-'.$i.'"></span></td>';
		$jsend .= '$(function () {
                                $(\'#time-'.$i.'\').countdown({since: "-'.(time()-$row['time']).'S", compact: true});
                        });';

		echo '<td>'. $row['uptime'] .'</td>';
		echo '<td>';
		if(empty($row['mtotal'])) {
			echo "N/A";
		} else {
			$mp = ($row['mused'])/$row['mtotal']*100;
			$classm = ( $mp > 70 ? ( $mp > 85 ? 'bar-danger' : 'bar-warning') : '');
			$used = $row['mused'];
			echo '<div class="progress"><div class="bar '. $classm .'" style="width:'. $mp .'%"><div class="bartext">'. $used .'/'. $row['mtotal'] .'MB</div></div></div>';
		}
		echo '<br /><a href="grapher.php?uid='.$row['uid'].'&type=memory&interval=1h" rel="lightbox-'.$row['uid'].'-memory">1h</a> <a href="grapher.php?uid='.$row['uid'].'&type=memory&interval=3h" rel="lightbox-'.$row['uid'].'-memory">3h</a> <a href="grapher.php?uid='.$row['uid'].'&type=memory&interval=6h" rel="lightbox-'.$row['uid'].'-memory">6h</a> <a href="grapher.php?uid='.$row['uid'].'&type=memory&interval=12h" rel="lightbox-'.$row['uid'].'-memory">12h</a> <a href="grapher.php?uid='.$row['uid'].'&type=memory&interval=1d" rel="lightbox-'.$row['uid'].'-memory">1d</a></td>';
		echo '<td class="5pad">';
		if(isset($row['diskused'])) {
			$mp = ($row['diskused']/$row['disktotal'])*100;
			$classd = ( $mp > 70 ? ( $mp > 85 ? 'bar-danger' : 'bar-warning') : '');
			echo '<div class="progress"><div class="bar '. $classd .'" style="width:'. $mp .'%"><div class="bartext">'. format_kbytes($row['diskused']) .'/'. format_kbytes($row['disktotal']) .'GB</div></div></div>';
		} else {
			echo 'N/A';
		}
		echo '<br /><a href="grapher.php?uid='.$row['uid'].'&type=disk&interval=1h" rel="lightbox-'.$row['uid'].'-disk">1h</a> <a href="grapher.php?uid='.$row['uid'].'&type=disk&interval=3h" rel="lightbox-'.$row['uid'].'-disk">3h</a> <a href="grapher.php?uid='.$row['uid'].'&type=disk&interval=6h" rel="lightbox-'.$row['uid'].'-disk">6h</a> <a href="grapher.php?uid='.$row['uid'].'&type=disk&interval=12h" rel="lightbox-'.$row['uid'].'-disk">12h</a> <a href="grapher.php?uid='.$row['uid'].'&type=disk&interval=1d" rel="lightbox-'.$row['uid'].'-disk">1d</a></td>';
		echo '<td><div style="display:block; margin: 3px; padding: 2px;">';
		echo '<span class="loadavg" style="background-color: #'.gen_color($row['load1']).'">'. sprintf('%.02f', $row['load1']) .'</span>&nbsp;';
		echo '<span class="loadavg" style="background-color: #'.gen_color($row['load5']).'">'. sprintf('%.02f', $row['load5']) .'</span>&nbsp;';
		echo '<span class="loadavg" style="background-color: #'.gen_color($row['load15']).'">'. sprintf('%.02f', $row['load15']) .'</span>&nbsp;';
		echo '</div><a href="grapher.php?uid='.$row['uid'].'&type=loadavg&interval=1h" rel="lightbox-'.$row['uid'].'-load">1h</a> <a href="grapher.php?uid='.$row['uid'].'&type=loadavg&interval=3h" rel="lightbox-'.$row['uid'].'-load">3h</a> <a href="grapher.php?uid='.$row['uid'].'&type=loadavg&interval=6h" rel="lightbox-'.$row['uid'].'-load">6h</a> <a href="grapher.php?uid='.$row['uid'].'&type=loadavg&interval=12h" rel="lightbox-'.$row['uid'].'-load">12h</a> <a href="grapher.php?uid='.$row['uid'].'&type=loadavg&interval=1d" rel="lightbox-'.$row['uid'].'-load">1d</a>';
		echo '</td>';
		echo '<td>Rx: '.format_bytes($row['rx']).'/s<br />Tx: '.format_bytes($row['tx']).'/s<br />';
		echo '<a href="grapher.php?uid='.$row['uid'].'&type=transfer&interval=1h" rel="lightbox-'.$row['uid'].'-transfer">1h</a> <a href="grapher.php?uid='.$row['uid'].'&type=transfer&interval=3h" rel="lightbox-'.$row['uid'].'-transfer">3h</a> <a href="grapher.php?uid='.$row['uid'].'&type=transfer&interval=6h" rel="lightbox-'.$row['uid'].'-transfer">6h</a> <a href="grapher.php?uid='.$row['uid'].'&type=transfer&interval=12h" rel="lightbox-'.$row['uid'].'-transfer">12h</a> <a href="grapher.php?uid='.$row['uid'].'&type=transfer&interval=1d" rel="lightbox-'.$row['uid'].'-transfer">1d</a>';
		echo '</td>';

//		echo '<td>Rx: '.(round($row['rx']/1024, 2)).' KB/s<br />Tx: '.(round($row['tx']/1024, 2)).' KB/s</td>';
		echo '</tr>';

		if ($servicecount[0] > 0) {
		$dbq = $db->prepare('SELECT * FROM processes WHERE uid = ? ORDER BY name ASC');
		$dbr = $dbq->execute(array($row['uid']));
		echo '<tr>';
		echo '<td class="service-list" colspan="6"><strong>Services:</strong><ul class="services">';
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
			echo '<tr><td colspan="6" style="text-align:left;"><strong>Notes: </strong>'.$row['note'].'</td></tr>';
		}
		$i++;
		$lbjs .= "
$(function() { $('a[rel=lightbox-".$row['uid']."-load]').lightBox({fixedNavigation:true}); }); 
$(function() { $('a[rel=lightbox-".$row['uid']."-disk]').lightBox({fixedNavigation:true}); }); 
$(function() { $('a[rel=lightbox-".$row['uid']."-memory]').lightBox({fixedNavigation:true}); });
$(function() { $('a[rel=lightbox-".$row['uid']."-transfer]').lightBox({fixedNavigation:true}); }); ";
	}

		/* From http://www.php.net/manual/en/function.filesize.php#100097, removed bytes*/
	function format_kbytes($size) {
		return round($size/1024/1024, 2);
	}

	function format_bytes($size) {
		if ($size > (1024*1024)) {
			return round($size/1024/1024, 2).' MB';
		} elseif ($size > 1024) {
			return round($size/1014, 2).' KB';
		} else {
			return $size.' B';
		}
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
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>OpenStatus - Server Statistics</title>
                <link href="css/bootstrap.min.css" rel="stylesheet"></link>
                <link href="css/bootstrap-responsive.min.css" rel="stylesheet"></link>
                <link href="css/bootstrap-adds.css" rel="stylesheet"></link>
                <script type="text/javascript" src="js/jquery.min.js"></script>
                <script tyle="text/javascript" src="js/jquery.countdown.min.js"></script>
                <script type="text/javascript" src="js/jquery.lightbox-0.5.min.js"></script>
                <script tyle="text/javascript" src="js/bootstrap.min.js"></script>
				<?php
					if (substr($_SERVER['SCRIPT_NAME'], -10) == '/index.php' || substr($_SERVER['SCRIPT_NAME'], -12) == '/history.php') {
						echo '<script type="text/javascript">
							function reloader() { window.location.reload() }
							refreshTimer = setInterval(\'reloader()\', 60000);
							</script>
						';
					}
				?>
				<link rel="stylesheet" type="text/css" href="css/jquery.lightbox-0.5.css" media="screen" />
        </head>
	<body>
		<div id="wrap">
                <div class="navbar navbar-inverse navbar-fixed-top">
                	<div class="navbar-inner">
                		<div class="container">
				          	<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
					            <span class="icon-bar"></span>
					            <span class="icon-bar"></span>
					            <span class="icon-bar"></span>
				          	</button>
                        	<a class="brand" href="/">Server Statistics</a>
                        	<div class="nav-collapse collapse">
	                        	<ul class="nav">
	                                <li><a href="/">View Status</a></li>
	                                <li><a href="/admin.php">Admin</a></li>
									<?php

									if (isset($_GET['logout'])) {
										setcookie('status-auth', '#');
										$auth = false;
										echo '</ul></div></div></div></div><div class="container"><p class="text-center">You have been logged out.</p></div>';
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
											echo '</ul></div></div></div></div><p>Invalid username or password.</p>';
											$auth = false;
										} else {
											setcookie('status-auth', $token);
											$auth = true;
										}
									}

									if ($auth === true) {
										echo '<li><a href="admin.php?logout">Log Out</a></li>';
										echo '</ul></div></div></div></div>';
									} else { 
										echo '</ul></div></div></div></div>';
									}

									//Login Form Here
									if ($auth === false && $requirelogin === true) {
										echo '
											<div class="container">
												<form action="admin.php" method="post" id="login">
													<table class="table">
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
												</form>
											</div>';

										echo '<div id="push"></div>';

										require('../footer.php');

									} else {

									        try {
									                $db = new PDO('sqlite:'. $db);
									        } catch (PDOException $e) {
									                error_log($_SERVER['SCRIPT_FILENAME'] .' - Unable to connect to the database: '. $e);
									                die('Unable to connect to the database - please try again later.');
									        }
									}

									include 'updates.php';

									?>