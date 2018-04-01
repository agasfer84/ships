<?php
//include $_SERVER['DOCUMENT_ROOT']."/dbconnect.php";
include $_SERVER['DOCUMENT_ROOT']."/ships.php";

$shiprequest=$_REQUEST["shiprequest"];
$shipid=$_GET["shipid"];

if($shiprequest=="shipinit"){
    $Ships = new Ships();
    $init_ships=$Ships->initShips();
    $result=array($init_ships);
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($result);
}

if($shiprequest=="shipinfo"&&$shipid){

    $Ships = new Ships();
    $shipinfo = $Ships->getShipById($shipid);
    $cannons = $Ships->getCannonsByShipId($shipid, false);

    $result=array(
        "shipinfo" => $shipinfo,
        "cannons"=> $cannons
    );

    header("Content-type: application/json; charset=utf-8");
    echo json_encode($result);

}

if($shiprequest=="fire"){

    $target_list= json_decode($_POST["json_string"], false);
    $Ships = new Ships();
    $fire = $Ships->fire($target_list);
    //$result = $fire;
    $enemy_fire = $Ships->enemy_fire();
    $result = array_merge($fire,$enemy_fire);
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($result);

}


if($shiprequest=="test"){
    //http://localhost:8086/shipinfo.php?shiprequest=test
    //$Ships = new Ships();
    //$test=$Ships->max_ship_strength();
    $test=round(0.5);
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($test);
}


