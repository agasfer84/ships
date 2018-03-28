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

        <div id="cannons"></div>
        </div>
    </div>
    <div id="battle_fraim">
        <div id="rus_column" class="inline columns">
            <ul id="rus_ul" class="ships_ul">
            <?php
            /*
            $Ships = new Ships();
            foreach($Ships->initShips()["rus_ships"] as $rusship)
            {
                echo "<li>";
                echo "<a href='javascript:void(0);' onclick='shipInfo(".$rusship["id"].");'><p class='ship_name'>".$rusship["name"]."</p></a>";
                echo "<img src='/images/'".$rusship["image"]." />";
                echo "<p class='p_line' title='Пожары'>".$Ships->fires_line($rusship["id"])."</p>";
                echo "<p class='p_line' title='Затопления'>".$Ships->flooding_line($rusship["id"])."</p>";
                echo "<p class='p_line' title='Экипаж'>".$Ships->crew_line($rusship["id"])."</p>";
                echo "</li>";
            }
            */
            ?>
            </ul>
        </div>
        <div id="jap_column" class="inline columns">

            <ul id="jap_ul" class="ships_ul">
                <?php
                /*
                foreach($Ships->initShips()["jap_ships"] as $japship)
                {
                    echo "<li>";
                    echo "<a href='javascript:void(0);' onclick='shipInfo(".$japship["id"].");'><p class='ship_name'>".$japship["name"]."</p></a>";
                    echo "<img src='/images/'".$japship["image"]." />";
                    echo "<p class='p_line' title='Пожары'>".$Ships->fires_line($japship["id"])."</p>";
                    echo "<p class='p_line' title='Затопления'>".$Ships->flooding_line($japship["id"])."</p>";
                    echo "<p class='p_line' title='Экипаж'>".$Ships->crew_line($japship["id"])."</p>";
                    echo "</li>";
                }
                */
                ?>
            </ul>

        </div>
    </div>
    <div id="log_fraim">

        <button onclick="fire()">Огонь</button>
    </div>


</body>
</html>
