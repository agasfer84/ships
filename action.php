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
    $result = $init_ships;
}

if ($_action == "shipInfo" && $_id) {
    $Ships = new Ships();
    $shipinfo = $Ships->getShipById($_id);
    $cannons = $Ships->getCannonsByShipId($_id);
    $strength = $Ships->shipStrength($_id);
    $result = array(
        "shipinfo" => $shipinfo,
        "cannons" => $cannons,
        "strength_nominal" => $strength["value_nominal"],
        "strength_fact" => $strength["value_fact"]
    );
}

if ($_action == "shot") {
    $target_list = json_decode($_body , false);
    $Ships = new Ships();
    $player_shot = $Ships->shot($target_list);
    $ai_target_list = $Ships->getAiTargetList();
    $enemy_shot = $Ships->shot($ai_target_list);
    $result = array_merge($player_shot, $enemy_shot);
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
    $list_forces["all"] = $Forces->getForcesList();
    $list_forces["active"] = $Forces->getActiveForcesList();
    $result = $list_forces;
}

if ($_action == "regionsList") {
    $Forces = new Forces();
    $list_regions = $Forces->getRegionsWithForcesList();
    $result = $list_regions;
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

if ($_action == "deleteForce") {
    $force_id = json_decode($_body , false);
    $Forces = new Forces();
    $result = $Forces->deleteForce($force_id->id);
}

if ($_action == "sendForcesToRegion") {
    $forcesToRegion = json_decode($_body , false);
    $Forces = new Forces();
    $result = $Forces->setRegion($forcesToRegion->forces, $forcesToRegion->region_id);
}

if ($_action == "turn") {
    $Forces = new Forces();
    $Ships = new Ships();
    $Ships->fireRepareForAll();
    $ships_in_bases = $Forces->getShipsInBases();
    $Ships->floodingRepareForAll($ships_in_bases, $repare = 1);
    $Ships->crewRepareForAll($ships_in_bases);
    $Ships->setActionForAll();
    $result = $Forces->turn();
}

if ($_action == "checkSwitch") {
    $Ships = new Ships();

    if ($region = $Ships->getRegionForBattle()) {
        $result = $region;
    } else {
        $result = false;
    }
}

if ($_action == "test") {
    //http://localhost:8086/shipinfo.php?shiprequest=test
$target_list = json_decode('[{"ship_id": 2, "enemy_id": "21"}, {"ship_id": 4, "enemy_id": "22"}]', false);

    $Ships = new Ships();
    $Forces = new Forces();
    $result = $Forces->getShipsInBases();
    //$result =$target_list = $Ships->getAiTargetList();
//$result = $Ships->fire($target_list);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
//    $result = $Ships->ai_fire();
    //$result = $Ships->initShips();
    //$result = $Ships->getCannonsByShipId($shipid = 1, $enemy_id = false);

}

$response = $result;

header("Content-type: application/json; charset=utf-8");
echo json_encode($response);


