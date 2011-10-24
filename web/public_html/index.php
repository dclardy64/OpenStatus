<?php

$requirelogin = false; // Change this to true if you want to require a valid username and password to view the status page
$alerts_require_login = false;
require('../header.php');

if (($auth === false && $requirelogin === false) || $auth === true) {

	$jsend = '';

	if ($auth === true) {
		if (isset($_GET['ack'])) {
			$ack = intval($_GET['ack']);
			$ackq = $db->prepare('UPDATE alerts SET acked = 1 WHERE id = ?');
			$ackq->execute(array($ack));
			header('Location: index.php');
		}
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
			<tr><th colspan="6">Servers</th></tr>
			<tr>
				<th scope="col">Hostname</th>
				<th scope="col">Last Updated</th>
				<th scope="col" style="width: 98px">Uptime</th>
				<th scope="col" style="width: 98px">RAM</th>
				<th scope="col" style="width: 98px">Disk</th>
				<th scope="col" style="width: 98px">Load</th>
			</tr>
		</thead>
			<tbody>';


	$dbs = $db->prepare('SELECT * FROM servers WHERE disabled = 0 ORDER BY hostname ASC');
	$result = $dbs->execute();
	$i = 0;

	while ($row = $dbs->fetch(PDO::FETCH_ASSOC)) {
		statusRow($row);
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
