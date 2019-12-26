<?php
$region_id = $_REQUEST["region_id"];
$switch = ($region_id) ? "battle" : "forces";
//$switch = "battle";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>
        Ships
    </title>
    <link href="styles/style.css" rel="stylesheet">
    <script src="js/rest.js"></script>
    <script src="js/main.js"></script>
</head>
<body>

<?php
//if ($switch == "forces") {
//
?>
<div id="map_frame">
</div>
<?php
//}
?>

<div id="top_frame">
    <div id="shipinfo_block">
        <p><span id="ship_name" style="font-weight:bold;"></span></p>
        <p><span id="ship_strength"></span></p>
        <p><span id="ship_speed"></span></p>
        <p><span id="ship_crew"></span></p>
        <p><span id="ship_fires"></span></p>
        <p><span id="ship_flooding"></span></p>
        <p><span id="ship_belt"></span></p>
        <p><span id="ship_armour_type"></span></p>
        <p><span id="ship_armour_effective"></span></p>

        <div id="cannons"></div>
    </div>
</div>

<?php
if ($switch == "battle") {
?>

<div id="battle_frame">
    <div id="player_column" class="inline columns">
        <p id="strength_player"></p>
        <p id="min_speed_player"></p>
        <ul id="player_ul" class="ships_ul">
        </ul>
    </div>
    <div id="log_frame" class="inline columns">
    </div>
    <div id="enemy_column" class="inline columns">
        <p id="strength_enemy"></p>
        <p id="min_speed_enemy"></p>
        <ul id="enemy_ul" class="ships_ul">
        </ul>
    </div>
</div>
<div><button id="fire_button" onclick="fire()" disabled="disabled">Огонь</button></div>

<?php
}
else if($switch == "forces") {
?>

    <div id="list_frame">
    </div>
    <div id="forces_frame">
    </div>

<div id="turn_frame">
    <button id="turn_button" onclick="turn()">Ход</button>
</div>

<?php }?>

</body>
</html>
