<?php
include $_SERVER['DOCUMENT_ROOT']."/ships.php";
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
    <script src="js/main.js"></script>
</head>
<body>

<div id="top_fraim">
    <div id="shipinfo_block">
        <p><span id="ship_name" style="font-weight:bold;"></span></p>
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
<div id="battle_fraim">
    <div id="rus_column" class="inline columns">
        <p id="min_speed_rus"></p>
        <ul id="rus_ul" class="ships_ul">
        </ul>
    </div>
    <div id="log_fraim" class="inline columns">
    </div>
    <div id="jap_column" class="inline columns">
        <p id="min_speed_jap"></p>
        <ul id="jap_ul" class="ships_ul">
        </ul>
    </div>
</div>
<div><button id="fire_button" onclick="fire()" disabled="disabled">Огонь</button></div>



</body>
</html>