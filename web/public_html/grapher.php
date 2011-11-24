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
include("pchart/class/pCache.class.php");

$myCache = new pCache();

if (isset($_GET['interval']) && $_GET['interval'] != "1w") {
	$interval = $_GET['interval'];
} else {
	$interval = "1h";
}

$uid = intval($_GET['uid']);

/* Create and populate the pData object */
$MyData = new pData();  
$queryInfo = queryInfo($interval);
$query = $db->prepare('SELECT * FROM servers WHERE uid = ?');
$query->execute(array($uid));
$info = $query->fetch(PDO::FETCH_ASSOC);

$query = $db->prepare('SELECT * FROM '.$queryInfo[5].' WHERE uid = ? AND time > ? ORDER BY time ASC');
$query->execute(array($uid, time()-$queryInfo[0]));

function calculatePoints($query, $interval, $field, $label, $unit, $makexvalues = true) {

	global $MyData;

	$xpoints = array();
	$points1 = array();
	$i = 0;

	while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
		// Don't have to do this anymore since the server handles it for us.
		//$rounded = floor($data['time']/60)*60;
		$rounded = $data['time'];
		if ($field == "diskused") {
			$pointvalue = ($data['diskused']/$data['disktotal']) * 100;
		} else {
			$pointvalue = $data[$field];
		}
		if ($interval[4] == "1w") {
			$xpoints[] = $rounded;
			$points1[] = $pointvalue;
		} elseif ($interval[4] == "1d") {
			$xpoints[] = $rounded;
			$points1[] = $pointvalue;
		} elseif ($interval[4] == "12h") {
			$xpoints[] = $rounded;
			$points1[] = $pointvalue;
		} elseif ($interval[4] == "6h") {
			if ($i % 2 == 0) {
				$xpoints[] = $rounded;
				$points1[] = $pointvalue;
			}
		} else {
			$xpoints[] = $rounded;
			$points1[] = $pointvalue;
		}
		$i++;
	}

	if ($interval[4] == "1h" || $interval[4] == "3h") {
		foreach ($xpoints as $key => $value) {
			if ($value+70 < $xpoints[$key+1]) {
				$diff = $value - $xpoints[$key+1];
				while ($diff < 0) {
					$xpoints[] = $value+60;
					$points1[] = VOID;
					$diff = $diff+60;
				}
			}
		}
		$j = 0;
		if ($interval[4] == "1h") {
			while (count($xpoints) < 60) {
				$xpoints[] = $xpoints[0]-($j*60);
				$points1[] = VOID;
				$j++;
			}
		} else {
			while (count($xpoints) < 180) {
				$xpoints[] = $xpoints[0]-($j*60);
				$points1[] = VOID;
				$j++;
			}
		}
	} elseif ($interval[4] == "12h") {
		foreach ($xpoints as $key => $value) {
			if ($value+310 < $xpoints[$key+1]) {
				$diff = $value - $xpoints[$key+1];
				while ($diff < 0) {
					$xpoints[] = $value+300;
					$points1[] = VOID;
					$diff = $diff+300;
				}
			}
		}
	} elseif ($interval[4] == "6h") {
		foreach ($xpoints as $key => $value) {
			if ($value+120 < $xpoints[$key+1]) {
				$diff = $value - $xpoints[$key+1];
				while ($diff < 0) {
					$xpoints[] = $value+120;
					$points1[] = VOID;
					$diff = $diff+120;
				}
			}
		}
	} else {
		foreach ($xpoints as $key => $value) {
			if ($value+1260 < $xpoints[$key+1]) {
				$diff = $value - $xpoints[$key+1];
				while ($diff < 0) {
					$xpoints[] = $value+1200;
					$points1[] = VOID;
					$diff = $diff+1200;
				}
			}
		}
	}

	$newpoints = array_combine($xpoints, $points1);
	ksort($newpoints, SORT_NUMERIC);
	foreach ($newpoints as $key => $value) {
		$newxvalues[] = $key;
		$newyvalues[] = $value;
	}
//	$label1 = $label.' - '.count($newxvalues);
	$MyData->addPoints($newyvalues, $label);
	$MyData->setAxisName(0, $label);
	$MyData->setAxisUnit(0, $unit);
	if ($makexvalues == true) {
		$MyData->addPoints($newxvalues, "Labels");
	}
}

function memoryGraph($queryInfo, $uid = 0, $label) {

	global $db, $MyData, $query, $info, $myCache;

	calculatePoints($query, $queryInfo, 'mused', $label, "MB");

	$MyData->loadPalette("pchart/palettes/openstatus.color", TRUE);
	$MyData->setSerieDescription("Labels","Time");
	$MyData->setAbscissa("Labels");
	$MyData->setXAxisDisplay(AXIS_FORMAT_TIME, $queryInfo[3]);
	$MyData->setAxisName(0, "");

	$myPicture = new pImage(700,230,$MyData);
	$ChartHash = $myCache->getHash($MyData);

	if ($myCache->isInCache($ChartHash)) {
		$myCache->strokeFromCache($ChartHash);
	} else {
		$myPicture->Antialias = TRUE;
		$myPicture->drawRectangle(0,0,699,229,array("R"=>0,"G"=>0,"B"=>0));
		$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>11));
		$myPicture->drawText(350,35, $label." - ".$queryInfo[1],array("FontSize"=>14,"Align"=>TEXT_ALIGN_MIDDLEMIDDLE));
		$myPicture->drawText(350,15,"Server: ".$info['hostname'], array("FontSize"=>14, "Align"=>TEXT_ALIGN_MIDDLEMIDDLE));
		$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>8));
		$myPicture->setGraphArea(60,40,650,200);

		$scaleSettings = array(
			"XMargin"=>10,
			"YMargin"=>10,
			"Floating"=>TRUE,
			"CycleBackground"=>FALSE,
			"LabelSkip"=>$queryInfo[2],
			"Mode"=>SCALE_MODE_START0,
			"DrawSubTicks"=>TRUE,
			"SkippedTickAlpha"=>0,
			"SubTickR"=>0,
			"SubTickG"=>0,
			"SubTickB"=>0
		);

		$myPicture->drawScale($scaleSettings);
		$myPicture->drawFilledSplineChart();
		$myCache->writeToCache($ChartHash,$myPicture);
	}

	$myPicture->autoOutput($ChartHash, "cache/memory-".$queryInfo[4].".png");

}

function loadavgGraph($queryInfo, $uid = 0, $label) {

	global $db, $MyData, $query, $info, $myCache;

	calculatePoints($query, $queryInfo, 'load15', "15 minutes", "", false);
	$query->execute();
	calculatePoints($query, $queryInfo, 'load5', "5 minutes", "", false);
	$query->execute();
	calculatePoints($query, $queryInfo, 'load1', "1 minute", "", true);

	$MyData->loadPalette("pchart/palettes/openstatus.color", TRUE);
	$MyData->setSerieDescription("Labels","Time");
	$MyData->setAbscissa("Labels");
	$MyData->setXAxisDisplay(AXIS_FORMAT_TIME, $queryInfo[3]);
	$MyData->setAxisName(0, "");

	$myPicture = new pImage(700,230,$MyData);
	$ChartHash = $myCache->getHash($MyData);

	if ($myCache->isInCache($ChartHash)) {
		$myCache->strokeFromCache($ChartHash);
	} else {

		$myPicture->Antialias = TRUE;
		$myPicture->drawRectangle(0,0,699,229,array("R"=>0,"G"=>0,"B"=>0));
		$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>11));
		$myPicture->drawText(350,35, $label." - ".$queryInfo[1],array("FontSize"=>14,"Align"=>TEXT_ALIGN_MIDDLEMIDDLE));
		$myPicture->drawText(350,15,"Server: ".$info['hostname'], array("FontSize"=>14, "Align"=>TEXT_ALIGN_MIDDLEMIDDLE));
		$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>8));
		$myPicture->setGraphArea(60,40,650,200);

		$scaleSettings = array(
			"XMargin"=>10,
			"YMargin"=>10,
			"Floating"=>TRUE,
			"CycleBackground"=>FALSE,
			"LabelSkip"=>$queryInfo[2],
			"Mode"=>SCALE_MODE_START0,
			"DrawSubTicks"=>TRUE,
			"SkippedTickAlpha"=>0,
			"SubTickR"=>0,
			"SubTickG"=>0,
			"SubTickB"=>0
		);
		
		$myPicture->drawLegend(510,20,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));
		$myPicture->drawScale($scaleSettings);
		$myPicture->drawSplineChart(array("BreakVoid"=>TRUE, "BreakR"=>255, "BreakG"=>255, "BreakB"=>255));
		$myCache->writeToCache($ChartHash,$myPicture);

	}

	$myPicture->autoOutput("cache/loadavg-".$queryInfo[4].".png");

}

function transferGraph($queryInfo, $uid = 0, $label) {

	global $db, $MyData, $query, $info, $myCache;

	function YFormat($size) {
		if ($size > (1024*1024)) {
			return round($size/1024/1024, 0).' MB';
		} else {
			return round($size/1014, 0).' KB/s';
		}
	}

	calculatePoints($query, $queryInfo, 'tx', "Transmitted", "/s", true);
	$query->execute();
	calculatePoints($query, $queryInfo, 'rx', "Received", "/s", false);

	$MyData->loadPalette("pchart/palettes/openstatus.color", TRUE);
	$MyData->setSerieDescription("Labels","Time");
	$MyData->setAbscissa("Labels");
	$MyData->setXAxisDisplay(AXIS_FORMAT_TIME, $queryInfo[3]);
	$MyData->setAxisDisplay(0, AXIS_FORMAT_CUSTOM, "YFormat");
	$MyData->setAxisName(0, "");

	$myPicture = new pImage(700,230,$MyData);
	$ChartHash = $myCache->getHash($MyData);

	if ($myCache->isInCache($ChartHash)) {
		$myCache->strokeFromCache($ChartHash);
	} else {

		$myPicture->Antialias = TRUE;
		$myPicture->drawRectangle(0,0,699,229,array("R"=>0,"G"=>0,"B"=>0));
		$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>11));
		$myPicture->drawText(350,35, $label." - ".$queryInfo[1],array("FontSize"=>14,"Align"=>TEXT_ALIGN_MIDDLEMIDDLE));
		$myPicture->drawText(350,15,"Server: ".$info['hostname'], array("FontSize"=>14, "Align"=>TEXT_ALIGN_MIDDLEMIDDLE));
		$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>8));
		$myPicture->setGraphArea(60,40,650,200);

		if ($MyData->getMax("Transmitted") > $MyData->getMax("Received")) {
			$scalemax = ceil($MyData->getMax("Transmitted") / 1024) * 1024;
		} else {
			$scalemax = ceil($MyData->getMax("Received") / 1024) * 1024;
		}
		if ($scalemax < 5) {
			$scalemax = 5;
		}
		$AxisBoundaries = array(0=>array("Min"=>0,"Max"=>$scalemax));

		$scaleSettings = array(
			"XMargin"=>10,
			"YMargin"=>10,
			"Floating"=>TRUE,
			"CycleBackground"=>FALSE,
			"LabelSkip"=>$queryInfo[2],
			"Mode"=>SCALE_MODE_MANUAL,
			"ManualScale"=>$AxisBoundaries,
			"DrawSubTicks"=>TRUE,
			"SkippedTickAlpha"=>0,
			"SubTickR"=>0,
			"SubTickG"=>0,
			"SubTickB"=>0
		);
		
		$myPicture->drawLegend(510,20,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL));


		$myPicture->drawScale($scaleSettings);
		$myPicture->drawSplineChart(array("BreakVoid"=>TRUE, "BreakR"=>255, "BreakG"=>255, "BreakB"=>255));
		$myCache->writeToCache($ChartHash,$myPicture);

	}

	$myPicture->autoOutput("cache/transfer-".$queryInfo[4].".png");

}

function diskGraph($queryInfo, $uid = 0, $label) {

	global $db, $MyData, $query, $info, $myCache;

	calculatePoints($query, $queryInfo, 'diskused', "Disk Used", "%");

	$MyData->loadPalette("pchart/palettes/openstatus.color", TRUE);
	$MyData->setSerieDescription("Labels","Time");
	$MyData->setAbscissa("Labels");
	$MyData->setXAxisDisplay(AXIS_FORMAT_TIME, $queryInfo[3]);
	$MyData->setAxisName(0, "");

	$myPicture = new pImage(700,230,$MyData);
	$ChartHash = $myCache->getHash($MyData);

	if ($myCache->isInCache($ChartHash)) {
		$myCache->strokeFromCache($ChartHash);
	} else {

		$myPicture->Antialias = TRUE;
		$myPicture->drawRectangle(0,0,699,229,array("R"=>0,"G"=>0,"B"=>0));
		$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>11));
		$myPicture->drawText(350,35, $label." - ".$queryInfo[1],array("FontSize"=>14,"Align"=>TEXT_ALIGN_MIDDLEMIDDLE));
		$myPicture->drawText(350,15,"Server: ".$info['hostname'], array("FontSize"=>14, "Align"=>TEXT_ALIGN_MIDDLEMIDDLE));
		$myPicture->setFontProperties(array("FontName"=>"pchart/fonts/calibri.ttf","FontSize"=>8));
		$myPicture->setGraphArea(60,40,650,200);

		$AxisBoundaries = array(0=>array("Min"=>0,"Max"=>100));
		$scaleSettings = array(
			"XMargin"=>10,
			"YMargin"=>10,
			"Floating"=>TRUE,
			"CycleBackground"=>FALSE,
			"LabelSkip"=>$queryInfo[2],
			"Mode"=>SCALE_MODE_MANUAL,
			"ManualScale"=>$AxisBoundaries,
			"DrawSubTicks"=>TRUE,
			"SkippedTickAlpha"=>0,
			"SubTickR"=>0,
			"SubTickG"=>0,
			"SubTickB"=>0
		);

		$myPicture->drawScale($scaleSettings);
		$myPicture->drawFilledSplineChart();
		$myCache->writeToCache($ChartHash,$myPicture);

	}
	$myPicture->autoOutput("cache/diskused-".$queryInfo[4].".png");

}

function queryInfo($int) {
	// Return values: interval length in seconds, title, # of X ticks to skip, time format, interval id, db table
	if ($int == "1h") {
		return array(1*3600, 'Past Hour', 4, 'G:i', '1h', 'history');
	} elseif ($int == "3h") {
		return array(3*3600, 'Past 3 Hours', 14, 'G:i', '3h', 'history');
	} elseif ($int == "6h") {
		return array(6*3600, 'Past 6 Hours', 14, 'G:i', '6h', 'history');
	} elseif ($int == "12h") {
		return array(12*3600, 'Past 12 Hours', 11, 'G:i', '12h', 'history5');
	} elseif ($int == "1d") {
		return array(24*3600, 'Past Day', 23, 'G:i', '1d', 'history5');
	} elseif ($int == "1w") {
		return array(7*24*3600, 'Past Week', 143, 'M j, G:i', '1w', 'history10');
	} else {
		return array(3600, 'Past Hour', 4, 'G:i', '1h', 'history');
	}
}

if ($_GET['type'] == "loadavg") {
	loadavgGraph($queryInfo, intval($_GET['uid']), "Load Average");
} elseif ($_GET['type'] == "memory") {
	memoryGraph($queryInfo, intval($_GET['uid']), "Memory Used");
} elseif ($_GET['type'] == "disk") {
	diskGraph($queryInfo, intval($_GET['uid']), "Disk Used");
} elseif ($_GET['type'] == "transfer") {
	transferGraph($queryInfo, intval($_GET['uid']), "Transfer");
} else {
	loadavgGraph($queryInfo, intval($_GET['uid']), "Load Average");
}
?>