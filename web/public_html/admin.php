<?php
$requirelogin = true;

require('../header.php');

echo '<div class="container">';

if ($auth === true) {


	if (isset($_GET['delete-service']) && $_GET['delete-service'] == "true") {
		$del = $db->prepare('DELETE FROM processes WHERE uid = ? AND name = ?');
		$del->execute(array($_GET['uid'], $_GET['name']));
		header('Location: admin.php');
	}

	if (isset($_GET['delete-server']) && $_GET['delete-server'] == "true") {
		$del = $db->prepare('DELETE FROM servers WHERE uid = ?');
		$del->execute(array($_GET['uid']));
		$del = $db->prepare('DELETE FROM processes WHERE uid = ?');
		$del->execute(array($_GET['uid']));
		$del = $db->prepare('DELETE FROM alerts WHERE uid = ?');
		$del->execute(array($_GET['uid']));

		header('Location: admin.php');
	}

	if (isset($_GET['editnote'])) {
		if (isset($_POST['noteedit'])) {
			$update = $db->prepare('UPDATE servers SET note = ? WHERE uid = ?');
			$update->execute(array($_POST['note'], intval($_GET['editnote'])));
			header('Location: admin.php');
		}
		echo '<table class="table table-bordered table-hover">';
		$query = $db->prepare('SELECT * FROM servers WHERE uid = ?');
		$query->execute(array(intval($_GET['editnote'])));
		$server = $query->fetch(PDO::FETCH_ASSOC);
		echo '<tr><th>Edit Note: '.$server['hostname'].'</th>';
		echo '<tr><td><form action="admin.php?editnote='.$server['uid'].'" method="post"><textarea name="note" style="width: 90%; height: 100px;">'.$server['note'].'</textarea></td></tr>';
		echo '<tr><td><input type="submit" name="noteedit" value="Save" /></td></tr>';
		echo '</table>';
	}

	if (isset($_GET['editserver'])) {
		if (isset($_POST['serveredit'])) {
			$updateh = $db->prepare('UPDATE servers set hostname = ? WHERE uid = ?');
			$uph = $updateh->execute(array($_POST['new_hostname'], intval($_GET['editserver'])));
			$updatei = $db->prepare('UPDATE servers set ip = ? WHERE uid = ?');
			$upi = $updatei->execute(array($_POST['new_ip'], intval($_GET['editserver'])));
			$updatep = $db->prepare('UPDATE servers set provider = ? WHERE uid = ?');
			$upp = $updatep->execute(array($_POST['new_provider'], intval($_GET['editserver'])));
			header('Location: admin.php');
		}
		echo '<table class="table table-bordered table-hover">';
		$query = $db->prepare('SELECT * FROM servers WHERE uid = ?');
		$query->execute(array(intval($_GET['editserver'])));
		$server = $query->fetch(PDO::FETCH_ASSOC);
		echo '<form action="admin.php?editserver='.$server['uid'].'" method="post">
					<tr><th colspan="3">Edit Server: ' .$server['hostname']. '</th></tr>
					<tr>
						<th>Name</th>
						<th>IP</th>
						<th>Provider</th>
				<tbody>
					<tr>
						<td><input type="name" name="new_hostname" style="width: 150px" value="' .$server['hostname'].'" /></td>
						<td><input type="name" name="new_ip" style="width: 150px" value="' .$server['ip'].'" /></td>
						<td><input type="name" name="new_provider" style="width: 150px" value="' .$server['provider'].'" /></td>
					</tr>
					<tr>
						<td colspan="3"><input type="submit" name="serveredit" value="Edit Server" /></td>
					</tr>
				</tbody>
		</table>';
	}

	if (isset($_POST['addservice'])) {
		$query = $db->prepare('INSERT INTO `processes` (`uid`, `process`, `name`, `disabled`, `status`) VALUES (?, ?, ?, ?, ?)');
		$q = $query->execute(array($_POST['uid'], $_POST['servicename'], $_POST['servicename'], 0, 1));
		if ($q === FALSE) {
			print_r($db->errorInfo());
		}
		header('Location: admin.php');
	}

	if (isset($_POST['addserver'])) {
		$query = $db->prepare('INSERT INTO `servers` (`hostname`, `ip`, `provider`, `disabled`) VALUES (?, ?, ?, ?)');
		$q = $query->execute(array($_POST['hostname'], $_POST['ip'], $_POST['provider'], 0));
		header('Location: admin.php');
	}

	$dbs = $db->prepare('SELECT * FROM servers WHERE disabled = 0 ORDER BY provider ASC, hostname ASC');
	$result = $dbs->execute();
	$i = 0;
	$provider = '';
	echo '
		<table class="table table-bordered table-hover">
		<thead>
			<tr><th colspan="5">Servers</th></tr>
			<tr>
				<th style="width: 25px">UID</th>
				<th style="width: 100px">Name</th>
				<th style="width: 150px">Services</th>
				<th>Notes</th>
				<th style="width: 150px">Actions</th>
			</tr>
		</thead>';
	while ($row = $dbs->fetch(PDO::FETCH_ASSOC)) {
		if ($row['provider'] != $provider) {
			echo '<tr><td colspan="6" style="text-align: left; vertical-align: middle; font-weight: bold; font-size: 10px; padding-left: 5px;">'. $row['provider'] .'</td></tr>';
			$provider = $row['provider'];
		}
		echo '
			<tr>
				<td>' .$row['uid']. '</td>
				<td>' .$row['hostname']. '</td><td>';
		$dbq = $db->prepare('SELECT * FROM processes WHERE uid = ? ORDER BY name ASC');
		$dbr = $dbq->execute(array($row['uid']));
		echo '<table class="table">';
		while ($service = $dbq->fetch(PDO::FETCH_ASSOC)) {
			echo '<tr><td>'. $service['name'] .'</td><td><a href="admin.php?delete-service=true&uid='.$service['uid'].'&name='.$service['name'].'">Delete</a></td></tr>';
		}
		echo '</table>';
		echo '</td>
				<td>'.$row['note'].'</td>
				<td><ul><li><a href="admin.php?editnote='.$row['uid'].'">Edit Notes</a></li><li><a href="admin.php?editserver='.$row['uid'].'">Edit Server</a></li><li><a href="admin.php?delete-server=true&uid='.$row['uid'].'">Delete Server</a></li></ul></td>
			</tr>';
	}
	echo '
		</table>';
	echo '
		<form action="admin.php?addserver" method="post">
		<table class="table table-bordered table-hover">
		<thead>
			<tr><th colspan="3">Add Server</th></tr>
			<tr>
				<th>Name</th>
				<th>IP</th>
				<th>Provider</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><input type="name" name="hostname" style="width: 150px" /></td>
				<td><input type="text" name="ip" style="width: 150px" /></td>
				<td><input type="text" name="provider" style="width: 150px" /></td>
			</tr>
			<tr>
				<td colspan="3"><input type="submit" name="addserver" value="Add Server" /></td>
			</tr>
		</tbody>
		</table>
		</form>';

	echo '
		<form action="admin.php?addservice" method="post">
		<table class="table table-bordered table-hover">
		<thead>
			<tr><th colspan="2">Add Service</th></tr>
			<tr>
				<th>Host</th>
				<th>Service Name</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<select name="uid">
						<option value="">---</option>';
	$dbq = $db->prepare('SELECT * FROM servers ORDER BY hostname ASC');
	$dbr = $dbq->execute();
	while ($host = $dbq->fetch(PDO::FETCH_ASSOC)) {
		echo '<option value="'.$host['uid'].'">'.$host['hostname'].'</option>';
	}
	echo '				</select>
				</td>
				<td><input type="text" name="servicename" style="width: 150px" /></td>
			</tr>
			<tr>
				<td colspan="2"><input type="submit" name="addservice" value="Add Service" /></td>
			</tr>
		</tbody>
		</table>
		</form>';
	echo '</div>';
}

echo '</div>';

echo '<div id="push"></div>';

require('../footer.php');
?>