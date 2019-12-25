<?php
require_once($_SERVER['DOCUMENT_ROOT']."/dbconnect.php");
?>
<?php

class Ships
{

    const RUS_PRECISION = 7;
    const JAP_PRECISION = 11;

    const RUS_PIERCING = 1;
    const JAP_PIERCING = 0.55;

    const RUS_FUGACITY = 0.7;
    const JAP_FUGACITY = 1;

    const GARVEY_ARMOUR = 1.2;
    const KRUPP_ARMOUR = 1.4;

    const BELT_CHANCE = 35;

    const INCHES_TO_MM = 25.4;

    public static $region_id;

    public function __construct()
    {
        $db = new Database();
        $this->db = $db;
    }

    public function getSides()
    {
        $result["player"] = 'russia';
        $result["enemy"] = 'japan';

        return $result;
    }

    public function getBattleForces()
    {
        if ($battle_region = $this->getRegionForBattle()["region_id"]) {
            self::$region_id = $battle_region;
        } else {
            return false;
        }

        $connection = $this->db;
        $query = "SELECT id, country FROM forces WHERE region_id = :region_id";
        $forces = $connection->prepare($query);
        $forces->setFetchMode(PDO::FETCH_ASSOC);
        $forces->execute(array("region_id" => self::$region_id));
        $forces = $forces->fetchAll();

        $result["player_force_id"] = [];
        $result["enemy_force_id"] = [];

        foreach ($forces as $force) {
            if ($force["country"] == $this->getSides()["player"]) {
                $result["player_force_id"][] = $force["id"];
            } else {
                $result["enemy_force_id"][] = $force["id"];
            }
        }

       return $result;
    }

    public function getRegionForBattle()
    {
        $this->checkDamageForAll();

        $connection = $this->db;
        $query = "SELECT DISTINCT f.country, f.region_id, r.region_name FROM forces f INNER JOIN ships s ON s.force_id = f.id
                  INNER JOIN regions r ON r.id = f.region_id
                  WHERE s.inaction = 1 AND s.isactive = 1 AND f.region_id IS NOT NULL";
        $forces = $connection->prepare($query);
        $forces->setFetchMode(PDO::FETCH_ASSOC);
        $forces->execute();
        $forces = $forces->fetchAll();

        if (count($forces) < 1) {
            return false;
        }

        $result = [];

        foreach ($forces as $force) {
            $result[$force["country"]][] = ["region_id" => $force["region_id"], "region_name" => $force["region_name"]];
        }

        if (!$result["russia"] || !$result["japan"]) {
            return false;
        }

        $intersect = array_intersect(array_column($result["russia"], "region_name", 'region_id'), array_column($result["japan"],"region_name", 'region_id'));

        if (count($intersect) > 0) {
            $id = array_keys($intersect)[0];

            return  ["region_id" => $id, "region_name" => $intersect[$id]];
        } else {
            return false;
        }
    }

    public function initShips()
    {
        $connection = $this->db;

        if (!$forces = $this->getBattleForces()) {
            return [];
        }

//        $this->checkDamageForAll();

        $player_forces = $forces["player_force_id"];
        $enemy_forces = $forces["enemy_force_id"];

        $forces_id = array_merge($player_forces, $enemy_forces);
        $forces_id_string = implode(",", $forces_id);
        $query_ships = "SELECT s.*, a.* FROM ships s LEFT JOIN armour a on a.shipid = s.id WHERE s.isactive=1 AND s.inaction=1 AND s.force_id IN ($forces_id_string) 
        ORDER BY s.order_id";
        $ships = $connection->prepare($query_ships);
        $ships->setFetchMode(PDO::FETCH_ASSOC);

        if (!$ships->execute()) {
            return [];
        }

        $ships = $ships->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        $temporary_result = [];
        $ships_speed["enemy_ships"] = [];
        $ships_speed["player_ships"] = [];

        foreach ($ships as $key => $ship) {
            $side_name = ($ship["country"] == $this->getSides()["player"]) ? "player_ships" : "enemy_ships";
            $ships_speed[$side_name][] = $this->getFactSpeed($ship["speed"], $ship["flooding"]);

            if((int)$ship["fires"] > 0) {
                $repare = 2 * (round((int)$ship["crew"] / 100)) - 1;
                self::fireRepare($ship["id"], $repare);
            }

            if(((int)$ship["fires"] >= 100) || ((int)$ship["flooding"] >= 100)) {
                self::checkDamage($ship["id"]);
            }

            $ships[$key] = $ship;
            $temporary_result[$side_name][] = $ship;
        }

        if (count($temporary_result["player_ships"]) < 1 || count($temporary_result["enemy_ships"]) < 1) {
            return [];
        }

        $result["enemy_ships_speed"] = min($ships_speed["enemy_ships"]);
        $result["player_ships_speed"] = min($ships_speed["player_ships"]);

        foreach ($ships as $ship) {
            $side_name = ($ship["country"] == $this->getSides()["player"]) ? "player_ships" : "enemy_ships";
            $enemy_forces_speed = ($side_name == "player_ships") ? $result["enemy_ships_speed"] : $result["player_ships_speed"];
            $ship["exit_button"] = self::exitButton($ship, $enemy_forces_speed);
            $result[$side_name][] = $ship;
        }

        $result["enemy_list"] = self::enemyList(self::getSides()["enemy"], $forces_id);

        return $result;
    }

    public function exitButton($ship, $enemy_forces_speed)
    {
        return ($ship["speed"] >= $enemy_forces_speed);
    }

    public function exitShip($shipid)
    {
        $connection = $this->db;
        $query = "UPDATE ships SET inaction = 0 WHERE id = :shipid";
        $result_query = $connection->prepare($query);

        return $result_query->execute(array("shipid" => $shipid));
    }

    public function checkDamage($shipid)
    {
        $connection = $this->db;
        $query = "UPDATE ships SET isactive = 0 WHERE id = :shipid";
        $result_query = $connection->prepare($query);
        $result_query->execute(array("shipid" => $shipid));
    }

    public function checkDamageForAll()
    {
        $connection = $this->db;
        $query = "UPDATE ships SET isactive = 0 WHERE fires >= 100 OR flooding >= 100";
        $result_query = $connection->prepare($query);
        $result_query->execute();
    }

    public function fireRepare($shipid, $repare)
    {
        $connection = $this->db;
        $query = "UPDATE ships SET fires = fires - :repare WHERE id = :shipid";
        $result_query = $connection->prepare($query);
        $result_query->execute(array("repare" => $repare, "shipid" => $shipid));
    }

    public function fire($target_list)
    {
      $cannons_with_target = [];
      $enemy_id_array = array_column($target_list, 'enemy_id');
      $enemies = $this->getShipsById($enemy_id_array);

      foreach ($target_list as $target) {
          $shipid = $target->ship_id;
          $enemy = $enemies[$target->enemy_id];

          if ($shipid) {
              $cannons =  $this->getCannonsByShipId($shipid, $enemy);
              $cannons_with_target = array_merge($cannons_with_target, $cannons);
          }
      }

      $result = [];

      foreach ($cannons_with_target as $cannon)
      {
        for ($i = 0; $i < $cannon["active_quantity"]; ++$i) {
            if ($this->fireChance($cannon)) {
                $fire_result = $this->fireResult($cannon);
                $cannon["fire_result_name"] = $fire_result["fire_result_name"];
                $cannon["fire_result_type_name"] = $fire_result["fire_result_type_name"];
                $cannon["fire_result_type"] = $fire_result["fire_result_type"];
                $cannon["fire_result"] = $fire_result["fire_result"];
                $cannon["fire_result_side"] = $fire_result["fire_result_side"];
                $this->fireExec($cannon);
                $result[] = $cannon;
            }
        }
    }

      return $result;
    }

    public function getAiTargetList()
    {
        $ai_list = self::getAiList();
        $player_force_id = $this->getBattleForces()["player_force_id"];
        $ships_strength = $this->shipsStrength($player_force_id);
        $target_id = self::mostStrongShipForAi($ships_strength);
        $result = [];

        foreach ($ai_list as $row) {
            $row->enemy_id = $target_id;
            $result[] = $row;
        }

        return $result;
    }

    public function getAiList()
    {
        $enemy_force_id = implode(",", $this->getBattleForces()["enemy_force_id"]);

        $connection = $this->db;
        $query_enemy = "SELECT id AS ship_id FROM ships WHERE isactive = 1 AND inaction = 1 AND force_id IN ($enemy_force_id)";
        $enemy_ships = $connection->prepare($query_enemy);
        $enemy_ships->setFetchMode(PDO::FETCH_OBJ);
        $enemy_ships->execute();
        $result = [];

        foreach ($enemy_ships as $enemy_ship) {
            $result[] = $enemy_ship;
        }

        return $result;
    }

    public function shipsStrength($force_id)
    {
        $force_id = implode(",", $force_id);
        $connection = $this->db;
        $query = "SELECT 
        c.caliber
        ,c.barrel_length
        ,c.quantity
        ,s.id
        ,s.displacement
        ,s.speed
        ,s.crew
        ,s.flooding
        ,s.fires
        ,a.armour_type
        ,a.belt  
        FROM cannons c 
        LEFT JOIN ships s ON c.shipid = s.id
        LEFT JOIN armour a ON a.shipid = s.id  
        WHERE s.force_id IN ($force_id) AND s.isactive = 1 AND s.inaction = 1";

        $force_ships = $connection->prepare($query);
        $force_ships->setFetchMode(PDO::FETCH_ASSOC);
        $force_ships->execute();
        $ships = [];
        $result = [];

        foreach ($force_ships as $key => $force_ship) {
            $force_ship["effective_armour"] = $this->getEffectiveArmour($force_ship["belt"], $force_ship["armour_type"]);
            $ships[$force_ship["id"]]["ship_strength_nominal"] = (int)$force_ship["displacement"] + (int)$force_ship["speed"] * 100 + (int)$force_ship["effective_armour"] * 10;
            $ships[$force_ship["id"]]["cannons_strength_nominal"] += ceil(pow($force_ship["caliber"], 2) * $force_ship["quantity"] * ($force_ship["barrel_length"] / 10));
            $ships[$force_ship["id"]]["ship_strength_fact"] = (int)$force_ship["displacement"] + self::getFactSpeed((int)$force_ship["speed"], (int)$force_ship["flooding"]) * 100 + (int)$force_ship["effective_armour"] * 10;
            $ships[$force_ship["id"]]["cannons_strength_fact"] += ceil(pow($force_ship["caliber"], 2) * self::getFactCannons((int)$force_ship["quantity"], (int)$force_ship["fires"]) * ($force_ship["barrel_length"] / 10));
        }

        foreach ($ships as $key => $ship) {
            $result[] =  array("id" => $key, "value_nominal" => $ship["ship_strength_nominal"] + $ship["cannons_strength_nominal"], "value_fact" => $ship["ship_strength_fact"] + $ship["cannons_strength_fact"]);
        }

        return $result;
    }

    public function calculateShipStrength()
    {

    }

    public static function forceStrength($ships_strength)
    {
        $nominal = array_sum(array_column($ships_strength, "value_nominal"));
        $fact = array_sum(array_column($ships_strength, "value_fact"));

        return array("nominal" => $nominal, "fact" => $fact);
    }


    public static function mostStrongShipForAi($ships_strength)
    {
        $max = 0;

        foreach ($ships_strength as $ship) {
            if ($ship["value_nominal"] < $max) continue;

            $max = $ship["value_nominal"];
        }

        $key = array_search($max, array_column($ships_strength, "value_nominal"));

        return $ships_strength[$key]["id"];
    }


    public function fireResult($item_fire)
    {
        $chance_rand = rand(1, 100);
        $side = ($item_fire["country"] == $this->getSides()["player"]) ? "player" : "enemy";
        $country_precision = self::RUS_PRECISION;

        if ($item_fire["country"] == "russia") {
            $country_precision = self::RUS_PRECISION;
        } else if ($item_fire["country"] == "japan") {
            $country_precision = self::JAP_PRECISION ;
        }

        $precision = (100 - $country_precision * ($item_fire["enemy_ship_length"] / 100));

        if ($chance_rand > $precision) {
            $fire_type = self::fireType();
            $result["fire_result"] = true;
            $result["fire_result_name"] = "Попадание";
            $result["fire_result_side"] = $side;
            $result["fire_result_type_name"] = $fire_type["fire_result_type_name"];
            $result["fire_result_type"] = $fire_type["fire_result_type"];
        } else {
            $result["fire_result"] = false;
            $result["fire_result_name"] = "Промах";
            $result["fire_result_side"] = $side;
            $result["fire_result_type_name"] = "";
            $result["fire_result_type"] = "";
        }

        return $result;
    }

    public function fireType()
    {
        $type_rand = rand(1,100);

        if($type_rand < self::BELT_CHANCE){
            $result["fire_result_type_name"] = "Бортовая броня";
            $result["fire_result_type"] = "belt";
        } else {
            $result["fire_result_type_name"] = "Надстройки";
            $result["fire_result_type"] = "superstructure";
        }

        return $result;
    }

    public function fireExec($item_fire)
    {
        $shipid = $item_fire["enemy_id"];
        $target_ship = $this->getShipById($shipid);
        $fugacity = 1;
        $piercing = 1;

        if ($item_fire["country"] == "russia") {
            $fugacity = self::RUS_FUGACITY;
            $piercing = self::RUS_PIERCING;
        } else if ($item_fire["country"] == "japan") {
            $fugacity = self::JAP_FUGACITY;
            $piercing = self::JAP_PIERCING;
        }

        if ($item_fire["fire_result_type"] == "superstructure")
         {
           $fire_level = ceil(($item_fire["caliber"] * self::INCHES_TO_MM * 100 * $fugacity) / $target_ship["displacement"]);
           $crew_level = ceil($fire_level / 2);
           $connection = $this->db;

           $query = "UPDATE ships SET fires = fires + :fire_level, crew = crew - :crew_level WHERE id = :shipid;";
           $result_query = $connection->prepare($query);
           $result_query->execute(array("fire_level" => $fire_level, "shipid" => $shipid, "crew_level" => $crew_level));

         }

        if ($item_fire["fire_result_type"] == "belt")
        {
            $flooding_level = 0;

            if ($item_fire["caliber"] * self::INCHES_TO_MM > $target_ship["effective_armour"]) {
                $flooding_level = ceil(pow(($item_fire["caliber"] * self::INCHES_TO_MM * $piercing), 2) / $target_ship["displacement"]);
            }

            $connection = $this->db;
            $query = "UPDATE ships SET flooding = flooding + :flooding_level WHERE id = :shipid";
            $result_query = $connection->prepare($query);
            $result_query->execute(array("flooding_level" => $flooding_level, "shipid" => $shipid));
        }

    }

    public function fireChance($item_fire)
    {
        $caliber_penalty = (2 * (int)$item_fire["caliber"]) / 12;
        $barrel_length_penalty = (50 -(int)$item_fire["barrel_length"]) / 5;
        $rand = rand(1, 10);
        $chance = 10 / ($barrel_length_penalty * $caliber_penalty);

        return ($chance > $rand);
    }

    public function enemyList($enemy, $force_id_array)
    {
        $connection = $this->db;
        $force_id_string = implode(",", $force_id_array);
        $query = "SELECT * FROM ships WHERE country = :enemy AND force_id IN ($force_id_string)";
        $ships = $connection->prepare($query);
        $ships->setFetchMode(PDO::FETCH_ASSOC);
        $ships->execute(array("enemy" => $enemy));
        $result = $ships->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getShipById($shipid) {
        $connection = $this->db;
        $query = "SELECT s.*, a.* FROM ships s INNER JOIN armour a ON s.id = a.shipid WHERE s.id = :shipid";
        $result = $connection->prepare($query);
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $result->execute(array("shipid" => $shipid));
        $result = $result->fetch(PDO::FETCH_ASSOC);

        $result["fact_speed"] = $this->getFactSpeed($result["speed"], $result["flooding"]);
        $result["effective_armour"] = $this->getEffectiveArmour($result["belt"], $result["armour_type"]);

        return $result;
    }

    public function getShipsById($ship_id_array) {
        $ship_id_string = implode(",", $ship_id_array);
        $connection = $this->db;
        $query = "SELECT s.*, a.* FROM ships s INNER JOIN armour a ON s.id = a.shipid WHERE s.id IN ($ship_id_string)";
        $ships = $connection->prepare($query);
        $ships->setFetchMode(PDO::FETCH_ASSOC);
        $ships->execute();
        $ships = $ships->fetchAll(PDO::FETCH_ASSOC);
        $result = [];

        foreach ($ships as $ship) {
            $ship["fact_speed"] = $this->getFactSpeed($ship["speed"], $ship["flooding"]);
            $ship["effective_armour"] = $this->getEffectiveArmour($ship["belt"], $ship["armour_type"]);
            $result[$ship["id"]] = $ship;
        }

        return $result;
    }

    public function getFactSpeed($speed, $ship_flooding)
    {
        $k_flooding = (100 - $ship_flooding) / 100;
        $result = ceil((int)$speed * $k_flooding);

        return $result;
    }

    public function getFactCannons($cannon_quantity, $ship_fires)
    {
        $k_fires = (100 - $ship_fires) / 100;
        $result = ceil((int)$cannon_quantity * $k_fires);

        return $result;
    }

    public function getEffectiveArmour($belt, $armour_type)
    {
        $k_armour = 1;

        if ($armour_type == "garvey") {
            $k_armour = self::GARVEY_ARMOUR;
        }

        if($armour_type == "krupp"){
            $k_armour = self::KRUPP_ARMOUR;
        }

        return round($belt * $k_armour, 2);
    }

    public function getCannonsByShipId($shipid, $enemy) {
        $ship = $this->getShipById($shipid);

        $connection = $this->db;
        $query = "SELECT c.*, s.name, s.country FROM cannons c INNER JOIN ships s ON s.id = c.shipid WHERE c.shipid = :shipid";
        $cannons = $connection->prepare($query);
        $cannons->setFetchMode(PDO::FETCH_ASSOC);
        $cannons->execute(array("shipid" => $shipid));
        $result = [];

        foreach ($cannons as $key => $cannon) {
           $result[$key] = $cannon;
           $result[$key]["active_quantity"] = $this->getFactCannons((int)$cannon["quantity"], $ship["fires"]);

           if ($enemy) {
               $result[$key]["enemy_id"] = $enemy["id"];
               $result[$key]["enemy_name"] = $enemy["name"];
               $result[$key]["enemy_ship_length"] = $enemy["length"];
           }
        }

        return $result;
    }

}
?>