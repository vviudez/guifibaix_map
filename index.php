<?php

date_default_timezone_set("Europe/Madrid");

include_once("_config/config.inc.php");
include_once("_classes/mysql.class.php");


include_once("include/GoogleMap.php");
include_once("include/JSMin.php");

$MAP_OBJECT = new GoogleMapAPI(); 
$MAP_OBJECT->_minify_js = isset($_REQUEST["min"])?FALSE:TRUE;
//$MAP_OBJECT->disableSidebar();
$MAP_OBJECT->width="100%";
$MAP_OBJECT->height="100%";
$MAP_OBJECT->setMapType("SATELLITE");

$links_array=array();

$dbSess = new MySQL(false,BBDD_NAME, BBDD_SERVER, BBDD_USER, BBDD_PWD, BBDD_CHARSET);
if (! $dbSess->Open(BBDD_NAME)) $dbSess->Kill();
                        
$rows=$dbSess->QueryArray("SELECT * FROM nodos");



foreach($rows as $nodoDB) {

    $lastupdate = strtotime($nodoDB["Actualizado"]);
    $curtime = time();
    
    //echo $arrnodo["hostname"] . " -- " . $curtime . " - " . $lastupdate . " = " . ($curtime-$lastupdate) . "<br/>";
    $json=$nodoDB["JSON"];
    $arrnodo=json_decode($json,true);
    
    $icon="/img/ok_32.png";

	if ($arrnodo["_id"]!="NanoBridge") {
		if(($curtime-$lastupdate) > 7200) {     // 2 horas
			$icon="/img/warning_32.png";
		}
		if(($curtime-$lastupdate) > 86400 ) {     // 1 dia
			$icon="/img/error_32.png";
		}
	}
    //var_dump($arrnodo);
    $MAP_OBJECT->addMarkerByCoords($arrnodo["lon"],$arrnodo["lat"],strtoupper($arrnodo["aliases"][0]["alias"]), "<b>".$arrnodo["hostname"] . "</b><br/>" . strtoupper($arrnodo["aliases"][0]["alias"])."<br/>Last Update: " . date('d/m/Y - H:i', $lastupdate),'',$icon);
    $links_array[strtoupper($arrnodo["aliases"][0]["alias"])]=$arrnodo["links"];
}

function qualityColor($quality){
    switch(true){
        case  (($signal == 0) && ($quality == 1.0)): $color="#000000"; break;
        case  ($quality == 1.0): $color="#00FF00"; break;
        case  (($quality >= 0.75) && ($quality < 1.0)): $color="#00FF00"; break;
        case  (($quality >= 0.5) && ($quality < 0.75)): $color="#0000FF"; break;
        case  (($quality >= 0.25) && ($quality < 0.5)): $color="#FF0000"; break;
        default: $color="#000000";
    }
    
    return $color;
}

function signalColor($signal){
    switch(true){
        case  ($signal == 0): $color="#000000"; break;
        case  ($signal >= -68): $color="#2e9c0b"; break;
        case  (($signal >= -72) && ($signal < -68)): $color="#e2d306"; break;
        case  (($signal >= -80) && ($signal < -72)): $color="#f76500"; break;
        case  (($signal >= -85) && ($signal < -80)): $color="#FF0000"; break;
        case  (($signal >= -90) && ($signal < -85)): $color="#0000ff"; break;
        case  (($signal >= -95) && ($signal < -90)): $color="#800085"; break;
        default: $color="#000000";
    }
    
    return $color;
}

function transparencyColor($signal){
    switch(true){
        case  ($signal == 0): $trans=0.0; break;
        case  ($signal >= -68): $trans="#2e9c0b"; break;
        case  (($signal >= -72) && ($signal < -68)): $trans=1.0; break;
        case  (($signal >= -80) && ($signal < -72)): $trans=0.8; break;
        case  (($signal >= -85) && ($signal < -80)): $trans=0.6; break;
        case  (($signal >= -90) && ($signal < -85)): $trans=0.4; break;
        case  (($signal >= -95) && ($signal < -90)): $trans=0.3; break;
        default: $trans=0.1;
    }
    
    return $trans;
}


function searchForTitle($title, $array) {
   foreach ($array as $key => $val) {
       if ($val['title'] === $title) {
           return $key;
       }
   }
   return false;
}

$arrSignalQuality1=array();
$arrSignalQuality2=array();
$arrSignalQuality3=array();
$arrSignalQuality4=array();
$arrSignalQuality5=array();
$arrSignalQuality6=array();
$arrSignalQuality7=array();


function assignToArrays($signal,$id) {
    global $arrSignalQuality1,$arrSignalQuality2,$arrSignalQuality3,$arrSignalQuality4,$arrSignalQuality5,$arrSignalQuality6,$arrSignalQuality7;
    switch(true){
        case  ($signal == 0): $arrSignalQuality7[]=$id; break;
        case  ($signal >= -68): $arrSignalQuality1[]=$id; break;
        case  (($signal >= -72) && ($signal < -68)): $arrSignalQuality2[]=$id; break;
        case  (($signal >= -80) && ($signal < -72)): $arrSignalQuality3[]=$id; break;
        case  (($signal >= -85) && ($signal < -80)): $arrSignalQuality4[]=$id; break;
        case  (($signal >= -90) && ($signal < -85)): $arrSignalQuality5[]=$id; break;
        case  (($signal >= -95) && ($signal < -90)): $arrSignalQuality6[]=$id; break;
        default: $color="#000000";
    }
    
}

foreach($MAP_OBJECT->_markers as $nodo) {
    //echo $nodo["html"];
    
    $links_nodo=$links_array[$nodo["title"]];
    
    foreach($links_nodo as $enlace) {
        //echo "ENLACE CON: ". $enlace["alias_remote"]."<br/>";
        $loc_nodo=searchForTitle(strtoupper($enlace["alias_remote"]),$MAP_OBJECT->_markers);
        if (($loc_nodo != false) && ($enlace["attributes"]["signal"]!=0)){
            $enlazado=$MAP_OBJECT->_markers[$loc_nodo];
            //$MAP_OBJECT->addPolyLineByCoords($nodo["lon"],$nodo["lat"],$enlazado["lon"],$enlazado["lat"],$id=false,$color=qualityColor($enlace["quality"]),$weight=0,$opacity=0);
            $id=$MAP_OBJECT->addPolyLineByCoords($nodo["lon"],$nodo["lat"],$enlazado["lon"],$enlazado["lat"],$id=false,$color=signalColor($enlace["attributes"]["signal"]),$weight=0,$opacity=transparencyColor($enlace["attributes"]["signal"]));
            assignToArrays($enlace["attributes"]["signal"],$id);
        }
    }
    //echo "<hr/>";
}

$dbSess->Close();
?>
<html lang="es">
<head>
<?=$MAP_OBJECT->getHeaderJS();?>
<?=$MAP_OBJECT->getMapJS();?>
<script type="text/javascript" charset="utf-8">

function hideLines(signal) {
    switch(signal)
    {
        case 1: <?php foreach ($arrSignalQuality1 as $lineID) {?>toggle(<?php echo $lineID;?>);<?php }?>break;
        case 2: <?php foreach ($arrSignalQuality2 as $lineID) {?>toggle(<?php echo $lineID;?>);<?php }?>break;
        case 3: <?php foreach ($arrSignalQuality3 as $lineID) {?>toggle(<?php echo $lineID;?>);<?php }?>break;
        case 4: <?php foreach ($arrSignalQuality4 as $lineID) {?>toggle(<?php echo $lineID;?>);<?php }?>break;
        case 5: <?php foreach ($arrSignalQuality5 as $lineID) {?>toggle(<?php echo $lineID;?>);<?php }?>break;
        case 6: <?php foreach ($arrSignalQuality6 as $lineID) {?>toggle(<?php echo $lineID;?>);<?php }?>break;
        case 7: <?php foreach ($arrSignalQuality7 as $lineID) {?>toggle(<?php echo $lineID;?>);<?php }?>break;
        default: break;
    }
}
</script>
<style>
html { height: 100%; width: 100%; }
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}

#content { height:100%; width: 100%;}

.headerGB {
    margin-top: 10px;

	font-family: "Verdana", Calibri, sans-serif;
	color: #000000;
	font-size: 24;
	font-weight: bold;

}

.leyenda{
    font-family: "Verdana", Calibri, sans-serif;
	color: #000000;
	font-size: 14;
    float: right;
    margin-top: 0px;
    padding-top: 0px;
    margin-right: 10px;
}

.leyenda ul {    
    list-style: none;
    text-align: center;
}
    
.leyenda ul li {
    display: block;
    float: left;
    width: 100px;
    text-align: center;
    font-size: 80%;
    list-style: none;
}

ul.legend-labels li span {
    display: block;
    float: left;
    text-align: center;
    height: 15px;
    width: 100px;
}

</style>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-59849199-1', 'auto');
  ga('send', 'pageview');

</script>
</head>

<body>

<table id="content" cellpadding="0" cellspacing="0">
<tr height="60"><td><img src="img/guifibaix-logo.png" style="float:left;margin-right:10px;"><h2 class="headerGB">Mapa Nodos GuifiBaix</h2></td>
<td align="right">
        <div class='leyenda'>
          <ul class='legend-labels'>
            <li onclick="javascript:hideLines(7);"><span style='background:#000000;'></span>0 dB</li>
            <li onclick="javascript:hideLines(1);"><span style='background:#2e9c0b;'></span>&gt; -65dB</li>
            <li onclick="javascript:hideLines(2);"><span style='background:#e2d306;'></span>-66dB a -72dB</li>
            <li onclick="javascript:hideLines(3);"><span style='background:#f76500;'></span>-73dB a -80dB</li>
            <li onclick="javascript:hideLines(4);"><span style='background:#FF0000;'></span>-81dB a -85dB</li>
            <li onclick="javascript:hideLines(5);"><span style='background:#0000ff;'></span>-86dB a -90dB</li>
            <li onclick="javascript:hideLines(6);"><span style='background:#800085;'></span>&lt; -90dB</li>
          </ul>
        </div>
</td></tr>
<tr><td colspan="2"><?=$MAP_OBJECT->printOnLoad();?><?=$MAP_OBJECT->printMap();?></td></tr>
</table>

</body>
</html>