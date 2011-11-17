<?php   

require_once('../config.php');
try {
	$db = new PDO('sqlite:'. $db);
} catch (PDOException $e) {
	error_log($_SERVER['SCRIPT_FILENAME'] .' - Unable to connect to the database: '. $e);
	die('Unable to connect to the database - please try again later.');
}

/* pChart library inclusions */
include("pchart/class/pData.class.php");
include("pchart/class/pDraw.class.php");
include("pchart/class/pImage.class.php");

function loadavgGraph($interval, $uid = 0) {
	global $db;
	$queryinterval = interval2seconds($interval);
	/* Create and populate the pData object */
	$MyData = new pData();  
	$query = $db->prepare('SELECT * FROM servers WHERE uid = ?');
	$query->execute(array($uid));
	$info = $query->fetch(PDO::FETCH_ASSOC);
	$query = $db->prepare('SELECT * FROM history WHERE uid = ? AND time > ? ORDER BY time ASC');
	$history = $query->execute(array($uid, time()-$queryinterval[0]));
	$points = array();

	while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
		$rounded = floor($data['time']/60)*60;
		$xpoints1[] = $rounded;
		$xpoints5[] = $rounded;
		$xpoints15[] = $rounded;
		$points1[] = $data['load1'];
		$points5[] = $data['load5'];
		$points15[] = $data['load15'];
	}

	foreach ($xpoints1 as $key => $value) {
		if ($value+60 < $xpoints1[$key+1]) {
			$diff = $value - $xpoints1[$key+1];
			while ($diff < 0) {
				$xpoints1[] = $value+60;
				$points1[] = VOID;
				$diff = $diff + 60;
			}
		}
	}

	$newpoints1 = array_combine($xpoints1, $points1);
	ksort($newpoints1, SORT_NUMERIC);
	foreach ($newpoints1 as $key => $value) {
		$newxvalues1[] = $key;
		$newyvalues1[] = $value;
	}

	foreach ($xpoints5 as $key => $value) {
		if ($value+60 < $xpoints5[$key+1]) {
			$diff = $value - $xpoints5[$key+1];
			while ($diff < 0) {
				$xpoints5[] = $value+60;
				$points5[] = VOID;
				$diff = $diff + 60;
			}
		}
	}

	$newpoints5 = array_combine($xpoints5, $points5);
	ksort($newpoints5, SORT_NUMERIC);
	foreach ($newpoints5 as $key => $value) {
		$newxvalues5[] = $key;
		$newyvalues5[] = $value;
	}

	foreach ($xpoints15 as $key => $value) {
		if ($value+60 < $xpoints15[$key+1]) {
			$diff = $value - $xpoints15[$key+1];
			while ($diff < 0) {
				$xpoints15[] = $value+60;
				$points15[] = VOID;
				$diff = $diff + 60;
			}
		}
	}

	$newpoints15 = array_combine($xpoints15, $points15);
	ksort($newpoints15, SORT_NUMERIC);
	foreach ($newpoints15 as $key => $value) {
		$newxvalues15[] = $key;
		$newyvalues15[] = $value;
	}

	$MyData->addPoints($newyvalues15,"15 Minute");
	$MyData->addPoints($newyvalues5,"5 Minute");
	$MyData->addPoints($newyvalues1,"1 Minute");

	$MyData->loadPalette("pchart/palettes/openstatus.color", TRUE);
	$MyData->setAxisName(0,"Load Average");
	$MyData->addPoints($newxvalues15, "Labels");
	$MyData->setSerieDescription("Labels","Time");
	$MyData->setAbscissa("Labels");
	$MyData->setXAxisDisplay(AXIS_FORMAT_TIME, $queryinterval[3]);

	/* Create the pChart object */
	$myPicture = new pImage(700,230,$MyData);

	/* Turn on Antialiasing */
	$myPicture->Antialias = TRUE;

	/* Add a border to the picture */
	$myPicture->drawRectangle(0,0,699,229,array("R"=>0,"G"=>0,"B"=>0));

	/* Write the chart title */ 
	$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>11));
	$myPicture->drawText(350,35,"Load Average - ".$queryinterval[1],array("FontSize"=>14,"Align"=>TEXT_ALIGN_BOTTOMMIDDLE));
	$myPicture->drawText(350,15,"Server: ".$info['hostname'], array("FontSize"=>14, "Align"=>TEXT_ALIGN_BOTTOMMIDDLE));

	/* Set the default font */
	$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>8));

	/* Define the chart area */
	$myPicture->setGraphArea(60,40,650,200);

	/* Draw the scale */
	$scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,"GridB"=>200,"CycleBackground"=>FALSE,"LabelSkip"=>$queryinterval[2], "Mode"=>SCALE_MODE_START0, "DrawSubTicks"=>TRUE, "SkippedTickAlpha"=>0, "SubTickR"=>0, "SubTickG"=>0, "SubTickB"=>0 );
	$myPicture->drawScale($scaleSettings);

	/* Turn on Antialiasing */
	$myPicture->Antialias = TRUE;

	/* Draw the line chart */
	$myPicture->drawSplineChart();

	/* Write the chart legend */
	$myPicture->drawLegend(500,20,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));

	/* Render the picture (choose the best way) */
	$myPicture->autoOutput("graphs/example.drawLineChart.simple.png");
}

function memoryGraph($interval, $uid = 0) {
	global $db;

	$queryinterval = interval2seconds($interval);

	/* Create and populate the pData object */
	$MyData = new pData();  

	$query = $db->prepare('SELECT * FROM servers WHERE uid = ?');
	$query->execute(array($uid));
	$info = $query->fetch(PDO::FETCH_ASSOC);

	$query = $db->prepare('SELECT * FROM history WHERE uid = ? AND time > ? ORDER BY time ASC');
	$history = $query->execute(array($uid, time()-$queryinterval[0]));
	//$xpoints = array();
	$points1 = array();
	while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
		$rounded = floor($data['time']/60)*60;
		$xpoints[] = $rounded;
		$points1[] = $data['mused'];
	}
	foreach ($xpoints as $key => $value) {
		if ($value+100 < $xpoints[$key+1]) {
			$diff = $value - $xpoints[$key+1];
			while ($diff < 0) {
				$xpoints[] = $value+60;
				$points1[] = VOID;
				$diff = $diff + 60;
			}
		}
	}

	$newpoints = array_combine($xpoints, $points1);
	ksort($newpoints, SORT_NUMERIC);
	foreach ($newpoints as $key => $value) {
		$newxvalues[] = $key;
		$newyvalues[] = $value;
	}
	$MyData->addPoints($newyvalues,"Memory Used");
	$MyData->loadPalette("pchart/palettes/openstatus.color", TRUE);
	$MyData->setAxisName(0,"Memory Used");
	$MyData->setAxisUnit(0, "MB");
	$MyData->addPoints($newxvalues, "Labels");
	$MyData->setSerieDescription("Labels","Time");
	$MyData->setAbscissa("Labels");
	$MyData->setXAxisDisplay(AXIS_FORMAT_TIME, $queryinterval[3]);

	/* Create the pChart object */
	$myPicture = new pImage(700,230,$MyData);

	/* Turn of Antialiasing */
	$myPicture->Antialias = FALSE;

	/* Add a border to the picture */
	$myPicture->drawRectangle(0,0,699,229,array("R"=>0,"G"=>0,"B"=>0));

	/* Write the chart title */ 
	$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>11));
	$myPicture->drawText(350,35,"Memory Used - ".$queryinterval[1],array("FontSize"=>14,"Align"=>TEXT_ALIGN_MIDDLEMIDDLE));
	$myPicture->drawText(350,15,"Server: ".$info['hostname'], array("FontSize"=>14, "Align"=>TEXT_ALIGN_MIDDLEMIDDLE));

	/* Set the default font */
	$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>8));

	/* Define the chart area */
	$myPicture->setGraphArea(60,40,650,200);

	/* Draw the scale */
	$scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,"GridB"=>200,"CycleBackground"=>FALSE,"LabelSkip"=>$queryinterval[2], "Mode"=>SCALE_MODE_START0, "DrawSubTicks"=>TRUE, "SkippedTickAlpha"=>0, "SubTickR"=>0, "SubTickG"=>0, "SubTickB"=>0 );
	$myPicture->drawScale($scaleSettings);

	/* Turn on Antialiasing */
	$myPicture->Antialias = TRUE;

	/* Draw the line chart */
	$myPicture->drawFilledSplineChart(array("BreakVoid"=>FALSE, "BreakR"=>0, "BreakG"=>0, "BreakB"=>255));

	/* Render the picture (choose the best way) */
	$myPicture->autoOutput("graphs/example.drawLineChart.simple.png");
}

function diskGraph($interval, $uid = 0) {
	global $db;

	$queryinterval = interval2seconds($interval);

	/* Create and populate the pData object */
	$MyData = new pData();  

	$query = $db->prepare('SELECT * FROM servers WHERE uid = ?');
	$query->execute(array($uid));
	$info = $query->fetch(PDO::FETCH_ASSOC);

	$query = $db->prepare('SELECT * FROM history WHERE uid = ? AND time > ? ORDER BY time ASC');
	$history = $query->execute(array($uid, time()-$queryinterval[0]));
	//$xpoints = array();
	$points1 = array();
	while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
		$rounded = floor($data['time']/60)*60;
		$xpoints[] = $rounded;
		$points1[] = ($data['diskused']/$data['diskfree'])*100;
	}
	foreach ($xpoints as $key => $value) {
		if ($value+100 < $xpoints[$key+1]) {
			$diff = $value - $xpoints[$key+1];
			while ($diff < 0) {
				$xpoints[] = $value+60;
				$points1[] = VOID;
				$diff = $diff + 60;
			}
		}
	}

	$newpoints = array_combine($xpoints, $points1);
	ksort($newpoints, SORT_NUMERIC);
	foreach ($newpoints as $key => $value) {
		$newxvalues[] = $key;
		$newyvalues[] = $value;
	}
	$MyData->addPoints($newyvalues,"Disk Space Used");
	$MyData->loadPalette("pchart/palettes/openstatus.color", TRUE);
	$MyData->setAxisName(0,"Disk Space Used");
	$MyData->setAxisUnit(0, "%");
	$MyData->addPoints($newxvalues, "Labels");
	$MyData->setSerieDescription("Labels","Time");
	$MyData->setAbscissa("Labels");
	$MyData->setXAxisDisplay(AXIS_FORMAT_TIME, $queryinterval[3]);

	/* Create the pChart object */
	$myPicture = new pImage(700,230,$MyData);

	/* Turn of Antialiasing */
	$myPicture->Antialias = FALSE;

	/* Add a border to the picture */
	$myPicture->drawRectangle(0,0,699,229,array("R"=>0,"G"=>0,"B"=>0));

	/* Write the chart title */ 
	$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>11));
	$myPicture->drawText(350,35,"Disk Space Used - ".$queryinterval[1],array("FontSize"=>14,"Align"=>TEXT_ALIGN_MIDDLEMIDDLE));
	$myPicture->drawText(350,15,"Server: ".$info['hostname'], array("FontSize"=>14, "Align"=>TEXT_ALIGN_MIDDLEMIDDLE));

	/* Set the default font */
	$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>8));

	/* Define the chart area */
	$myPicture->setGraphArea(60,40,650,200);

	/* Draw the scale */
	$AxisBoundaries = array(0=>array("Min"=>0,"Max"=>100));
	$scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,"GridB"=>200,"CycleBackground"=>FALSE,"DrawXLines"=>FALSE, "LabelSkip"=>$queryinterval[2], "Mode"=>SCALE_MODE_MANUAL, "ManualScale"=>$AxisBoundaries, "DrawSubTicks"=>TRUE, "SkippedTickAlpha"=>0, "SubTickR"=>0, "SubTickG"=>0, "SubTickB"=>0 );
	$myPicture->drawScale($scaleSettings);

	/* Turn on Antialiasing */
	$myPicture->Antialias = TRUE;

	/* Draw the line chart */
	$myPicture->drawFilledSplineChart(array("BreakVoid"=>FALSE, "BreakR"=>0, "BreakG"=>0, "BreakB"=>255));

	/* Render the picture (choose the best way) */
	$myPicture->autoOutput("graphs/example.drawLineChart.simple.png");
}
function interval2seconds($int) {
	if ($int == "1h") {
		return array(1*3600, 'Past Hour', 4, 'G:i');
	} elseif ($int == "3h") {
		return array(3*3600, 'Past 3 Hours', 14, 'G:i');
	} elseif ($int == "6h") {
		return array(6*3600, 'Past 6 Hours', 29, 'G:i');
	} elseif ($int == "12h") {
		return array(12*3600, 'Past 12 Hours', 59, 'G:i');
	} elseif ($int == "1d") {
		return array(24*3600, 'Past Day', (59*2)+1, 'G:i');
	} elseif ($int == "1w") {
		return array(7*24*3600, 'Past Week', (59*12)+11, 'M j, G:i');
	} else {
		return array(3600, 'Past Hour', 4, 'G:i');
	}
}

if (isset($_GET['interval'])) {
	$interval = $_GET['interval'];
} else {
	$interval = "1h";
}

if ($_GET['type'] == "loadavg") {
	loadavgGraph($interval, intval($_GET['uid']));
} elseif ($_GET['type'] == "memory") {
	memoryGraph($interval, intval($_GET['uid']));
} elseif ($_GET['type'] == "disk") {
	diskGraph($interval, intval($_GET['uid']));
} else {
	loadavgGraph($interval, intval($_GET['uid']));
}
?>