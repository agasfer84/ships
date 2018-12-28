<?php

require_once($_SERVER['DOCUMENT_ROOT']."/battle.php");
require_once($_SERVER['DOCUMENT_ROOT']."/forces.php");

$_action = $_REQUEST["action"];
$_id = $_REQUEST["id"];
$_body = file_get_contents('php://input');
$result = [];

if ($_action == "shipInit") {
    $Ships = new Ships();
    $init_ships = $Ships->initShips();
    $result = array($init_ships);
}

if ($_action == "shipInfo" && $_id) {
    $Ships = new Ships();
    $shipinfo = $Ships->getShipById($_id);
    $cannons = $Ships->getCannonsByShipId($_id, false);
    $result = array(
        "shipinfo" => $shipinfo,
        "cannons"=> $cannons
    );
}

if ($_action == "fire") {
    $target_list = json_decode($_body , false);
    $Ships = new Ships();
    $fire = $Ships->fire($target_list->target_list);
    //$result = $fire;
    $enemy_fire = $Ships->ai_fire();
    $result = array_merge($fire,$enemy_fire);
}

if ($_action == "exitShip")
{
    $Ships = new Ships();
    $shipinfo = $Ships->exitShip($_id);
}

if ($_action == "shipList") {
    $Forces = new Forces();
    $list_ships = $Forces->getShipList();
    $result = $list_ships;
}

if ($_action == "forcesList") {
    $Forces = new Forces();
    $list_forces = $Forces->getForcesList();
    $result = $list_forces;
}

if ($_action == "shipsToForce") {
    $ships_list = json_decode($_body , false);
    $Forces = new Forces();
    $result = $Forces->setShipsToForce($ships_list);
}

if ($_action == "createNewForce") {
    $force_name = json_decode($_body , false);
    $Forces = new Forces();
    $result = $Forces->createNewForce($force_name->force_name);
}

//createNewForce

if ($_action == "test") {
    //http://localhost:8086/shipinfo.php?shiprequest=test
    $target_list = json_decode('[{"ship_id": 2, "enemy_id": "21"}, {"ship_id": 4, "enemy_id": "22"}]', false);
    $Ships = new Ships();
    $result = $Ships->fire($target_list);
    $result = $Ships->ai_fire();
}

$response = $result;

header("Content-type: application/json; charset=utf-8");
echo json_encode($response);


