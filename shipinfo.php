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
    $result = $fire;
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($result);
    //echo json_encode($_POST);
    //echo json_encode($_POST["json_string"]);

}

if($shiprequest=="test"){
    $Ships = new Ships();
    /*$test=$Ships->fire_exec($item_fire=1);
    return $test;*/
}


