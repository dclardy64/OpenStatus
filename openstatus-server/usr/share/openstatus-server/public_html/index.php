<?php

$requirelogin = false; // Change this to true if you want to require a valid username and password to view the status page
$alerts_require_login = false;
require('../header.php');

if (($auth === false && $requirelogin === false) || $auth === true) {

	if ($auth === true) {
		if (isset($_GET['ack'])) {
			$ack = intval($_GET['ack']);
			$ackq = $db->prepare('UPDATE alerts SET acked = 1 WHERE id = ?');
			$ackq->execute(array($ack));
			header('Location: index.php');
		}
	}
	$jsend = '';

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

	echo '
			<div class="stats_container" id="stats">';


	if (($auth === true && $alerts_require_login === true) || ($requirelogin === false && $alerts_require_login === false)) {

		$alert_query = $db->prepare('SELECT * FROM alerts LEFT OUTER JOIN servers ON alerts.server_uid = servers.uid WHERE acked = 0 ORDER BY alert_time ASC');
		$alert_query->execute();
		$cq = $db->query('SELECT * FROM alerts WHERE acked = 0');

		if ($cq->fetchColumn() > 0) {
		echo '<table style="border: 1;" id="alerts">
			<thead>
				<tr>
					<th colspan="'.($auth === true ? '6' : '5').'">Alerts</th>
				</tr>
				<tr>
					<th scope="col">Hostname</th>
					<th scope="col">Module</th>
					<th scope="col">Time Since</th>
					<th scope="col">Level</th>
					<th scope="col">Value</th>';
		if ($auth === true)
			echo '		<th scope="col">Actions</th>';

		echo '
				</tr>
			</thead>
			<tbody>';
		}
		while ($alert = $alert_query->fetch(PDO::FETCH_ASSOC)) {
			echo '<tr class="'.$alert['level'].'"><td>'.$alert['hostname'].'</td><td>'.$alert['module'].'</td><td id="alert-'.$alert['id'].'"></td><td>'.$alert['level'].'</td><td>'.$alert['value'].'</td>'.($auth === true ? '<td><a href="index.php?ack='.$alert['id'].'">Acknowledge</a></td>' : '').'</tr>';
			$jsend .= '$(function () {
				$(\'#alert-'.$alert['id'].'\').countdown({since: "-'.(time()-$alert['alert_time']).'S", compact: true});
			});';

		}
		if ($cq->fetchColumn() > 0) {
			echo '</tbody>
			</table>';
		}
	}

	echo '
		<table style="border: 1;" id="servers">
		<thead>
			<tr><th colspan="8">Servers</th></tr>
			<tr>
				<th scope="col">Hostname</th>
				<th scope="col">Services</th>
				<th scope="col">Last Updated</th>
				<th scope="col">Uptime</th>
				<th scope="col">RAM</th>
				<th scope="col">Disk</th>
				<th scope="col">Load</th>
			</tr>
		</thead>
			<tbody>';


	$dbs = $db->prepare('SELECT * FROM servers WHERE disabled = 0 ORDER BY hostname ASC');
	$result = $dbs->execute();
	$i = 0;
	$provider = '';
	while ($row = $dbs->fetch(PDO::FETCH_ASSOC)) {
		$i++;
		$provider = $row['provider'];
	   	if ($row['status'] == "0") {
			echo '<tr style="text-align: center" class="offline">';
		} elseif ($row['uptime'] == "n/a") {
			echo '<tr style="text-align: center" class="online-but-no-data">';
		} else {
			echo '<tr style="text-align: center">';
		}
		echo '<td rowspan="2"><a href="/history.php?uid='. $row['uid'].'">'. $row['hostname'] .'</a></td>';
		echo '<td>';

		$dbq = $db->prepare('SELECT * FROM processes WHERE uid = ? ORDER BY name ASC');
		$dbr = $dbq->execute(array($row['uid']));
		echo '<table class="services">';
		while ($service = $dbq->fetch(PDO::FETCH_ASSOC)) {
			echo '<tr><td>'. $service['name'] .'</td><td>'. ($service['status'] == 0 ? '<img src="/images/up.png" />' : '<img src="/images/down.png" />') .'</td></tr>';
		}
		echo '</table>';
		echo '</td>';
		echo '<td><span id="time-'.$i.'"></span></td>';
		$jsend .= '$(function () {
                                $(\'#time-'.$i.'\').countdown({since: "-'.(time()-$row['time']).'S", compact: true});
                        });';

		echo '<td>'. $row['uptime'] .'</td>';
		echo '<td class="5pad">';
		if(empty($row['mtotal'])) {
			echo "N/A";
		} else {
			$mp = ($row['mused']-$row['mbuffers'])/$row['mtotal']*100;
			$used = $row['mused'] - $row['mbuffers'];
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
	   	if ($row['status'] == "0") {
			echo '<tr style="text-align: center" class="offline">';
		} elseif ($row['uptime'] == "n/a") {
			echo '<tr style="text-align: center" class="online-but-no-data">';
		} else {
			echo '<tr style="text-align: center">';
		}
		echo '<td colspan="6" style="text-align:left;"><strong>Notes: </strong>'.$row['note'].'</td></tr>';
	}

echo '
			</tbody>
	</table>
			</div>
			<script type="text/javascript">
				'. $jsend .'
			</script>';

}

require('../footer.php');
?>
