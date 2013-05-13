<?php

/*****************************************************************
* File: history.php
* Desc: Shows the alert history for a specific server
* Req: $_GET['uid'] (int) - `server`.`uid`
*****************************************************************/

$requirelogin = false; // Change this to true if you want to require a valid username and password to view this page
$alerts_require_login = false; // Change this to true if you want to require a login to view alerts
require('../header.php');

echo '<div class="container">';

$server_uid = intval($_GET['uid']);

if (($auth === false && $requirelogin === false) || $auth === true) {

	if ($auth === true) {
		if (isset($_GET['ack'])) {
			$ack = intval($_GET['ack']);
			$ackq = $db->prepare('UPDATE alerts SET acked = 1 WHERE id = ?');
			$ackq->execute(array($ack));
			header('Location: history.php?uid='.$server_uid);
		} elseif (isset($_GET['ackall'])) {
			$ack = intval($_GET['ackall']);
			$ackq = $db->prepare('UPDATE alerts SET acked = 1 WHERE server_uid = ?');
			$ackq->execute(array($ack));
			header('Location: history.php?uid='.$server_uid);
		}
	}

	echo '
		<table class="table table-bordered table-hover">
		<thead>
			<tr><th colspan="7">Servers</th></tr>
			<tr>
				<th scope="col">Name</th>
				<th scope="col" style="width: 98px">Last Updated</th>
				<th scope="col" style="width: 98px">Uptime</th>
				<th scope="col" style="width: 98px">RAM</th>
				<th scope="col" style="width: 98px">Disk</th>
				<th scope="col" style="width: 98px">Load</th>
				<th scope="col" style="width: 98px">Transfer</th> 
			</tr>
		</thead>
			<tbody>';

	$dbs = $db->prepare('SELECT * FROM servers WHERE disabled = 0 AND uid = ? ORDER BY hostname ASC');
	$result = $dbs->execute(array($server_uid));
	$i = 0;
	$jsend = '';
	while ($row = $dbs->fetch(PDO::FETCH_ASSOC)) {
		statusRow($row);
	}

	echo '
				</tbody>
		</table>';

	if (($auth === true && $alerts_require_login === true) || ($requirelogin === false && $alerts_require_login === false)) {
		$alert_query = $db->prepare('SELECT * FROM alerts WHERE server_uid = ? ORDER BY alert_time DESC');
		$alert_query->execute(array($server_uid));

		echo '<table class="table table-bordered table-hover">
			<thead>
				<tr>
					<th colspan="'.($auth === true ? '5' : '4').'">Alerts'.($auth === true ? ' - (<a href="history.php?ackall='.$server_uid.'&uid='.$server_uid.'">Acknowledge All</a>)' : '').'</th>
				</tr>
				<tr>
					<th scope="col">Module</th>
					<th scope="col">Date / Time</th>
					<th scope="col">Level</th>
					<th scope="col">Value</th>'.($auth === true ? '
					<th scope="col">Actions</th>' : '').'
				</tr>
			</thead>
			<tbody>';

		while ($alert = $alert_query->fetch(PDO::FETCH_ASSOC)) {
			echo '<tr class="'.$alert['level'].'"><td>'.$alert['module'].'</td><td id="alert-'.$alert['id'].'">'.date('M j, Y g:ia', $alert['alert_time']).'</td><td>'.$alert['level'].'</td><td>'.$alert['value'].'</td>';

			if ($auth === true) {
				echo '<td>';
				if ($alert['acked'] == 0)
					echo '<a href="history.php?ack='.$alert['id'].'&uid='.$alert['server_uid'].'">Acknowledge</a>';
				else
					echo 'N/A';
				echo '</td>';
			}

			echo '</tr>';

		}

		echo '</tbody>
		</table>';
	}

	echo '
				</div>
				<script type="text/javascript">
					'. $jsend .'
				</script>';

}

echo '</div>';

echo '<div id="push"></div>';

require('../footer.php');
?>
