<?php

function table_exists($mytable) {
	global $db;
	$return = false;
	$query = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
	while($table = $query->fetch(PDO::FETCH_ASSOC)) {
		if ($table['name'] == $mytable) {
			$return = true;
		}
	}
	return $return;
}

function column_exists($column, $table) {
	global $db;
	$return = false;
	$query = $db->query("PRAGMA table_info(".$table.")");
//	$query->execute(array($table));
	while ($info = $query->fetch(PDO::FETCH_ASSOC)) {
		if ($info['name'] == $column) {
			$return = true;
		}
	}
	return $return;
}
if (!isset($_GET['update'])) {
	if (table_exists("history") == false || table_exists("history5") == false || table_exists("history10") == false || column_exists("provider", "servers") == false || column_exists("node", "servers") == false || column_exists("rx", "servers") == false || column_exists("tx", "servers") == false) {
		echo '
			<table id="upgrade">
				<tr><th>Upgrade</th></tr>
				<tr><td>It looks like you\'ve upgraded OpenStatus from a previous version, and your database schema needs to be updated to enable new features.  <a href="/index.php?update=true">Click here</a> to run the database update.</td></tr>
			</table>';
	}
} elseif (isset($_GET['update']) && $_GET['update'] == "true") {
	if ($auth == true) {
		if (table_exists("history5") == false || table_exists("history10") == false || column_exists("provider", "servers") == false || column_exists("node", "servers") == false) {
			$db->query('CREATE TABLE "history" ( "uid" INTEGER, "time" INTEGER, "mtotal" TEXT, "mused" TEXT, "mfree" TEXT, "mbuffers" TEXT, "disktotal" TEXT, "diskused" TEXT, "diskfree" TEXT, "load1" TEXT, "load5" TEXT, "load15" TEXT, "tx" TEXT, "rx" TEXT);');
			$db->query('CREATE TABLE "history5" ( "uid" INTEGER, "time" INTEGER, "mtotal" TEXT, "mused" TEXT, "mfree" TEXT, "mbuffers" TEXT, "disktotal" TEXT, "diskused" TEXT, "diskfree" TEXT, "load1" TEXT, "load5" TEXT, "load15" TEXT, "tx" TEXT, "rx" TEXT);');
			$db->query('CREATE TABLE "history10" ( "uid" INTEGER, "time" INTEGER, "mtotal" TEXT, "mused" TEXT, "mfree" TEXT, "mbuffers" TEXT, "disktotal" TEXT, "diskused" TEXT, "diskfree" TEXT, "load1" TEXT, "load5" TEXT, "load15" TEXT, "tx" TEXT, "rx" TEXT);');
			$db->query('ALTER TABLE servers ADD COLUMN "rx" TEXT;');
			$db->query('ALTER TABLE servers ADD COLUMN "tx" TEXT;');
			$db->query('ALTER TABLE servers ADD COLUMN "provider" TEXT;');
			$db->query('ALTER TABLE servers ADD COLUMN "node" TEXT;');
		echo '
				<table id="upgrade">
					<tr><th>Upgrade</th></tr>
					<tr><td>Database upgrade complete!</tr></td>
				</table>';
		}
		rename("/usr/share/openstatus-server/updates.php", "/usr/share/openstatus-server/updates.php.bak");
	} else {
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
		include 'footer.php';
		die();
	}
}
?>