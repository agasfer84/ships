<?php
//include $_SERVER['DOCUMENT_ROOT']."/dbconnect.php";
include $_SERVER['DOCUMENT_ROOT']."/ships.php";

$shiprequest=$_GET["shiprequest"];
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
    $cannons = $Ships->getCannonsByShipId($shipid);

    $result=array(
        "shipinfo" => $shipinfo,
        "cannons"=> $cannons
    );

    header("Content-type: application/json; charset=utf-8");
    echo json_encode($result);

}


