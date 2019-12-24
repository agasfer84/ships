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
            $ships_speed[$side_name][] = self::getFactSpeed($ship["speed"], $ship["flooding"]);

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
      $result=[];

      foreach ($target_list as $target) {
          $shipid = $target->ship_id;
          $enemy_id = $target->enemy_id;

          if ($shipid) {
              $cannons =  $this->getCannonsByShipId($shipid, $enemy_id);
              $result[] = $cannons;
          }
      }

      $result2 = [];

      for ($i = 0; $i < count($result); ++$i) {
        foreach ($result[$i] as $res)
        {
            $result2[] = $res;
        }

    }

      $result3 = [];

      foreach ($result2 as $res2)
      {
        for($i = 0; $i < $res2["active_quantity"]; ++$i) {

            if (self::fire_chance($res2)) {
                $fire_result = self::fire_result($res2);
                $res2["fire_result_name"] = $fire_result["fire_result_name"];
                $res2["fire_result_type_name"] = $fire_result["fire_result_type_name"];
                $res2["fire_result_type"] = $fire_result["fire_result_type"];
                $res2["fire_result"] = $fire_result["fire_result"];
                $res2["fire_result_side"] = $fire_result["fire_result_side"];
                self::fire_exec($res2);
                $result3[] = $res2;
            }
        }
    }

      return $result3;
    }

    public function ai_fire()
    {
        $result = [];
        $target_list = self::ai_list();
        $player_force_id = $this->getBattleForces()["player_force_id"];

        foreach ($target_list as $target) {
            $shipid = $target["id"];
            $enemy_id = self::max_ship_strength($player_force_id);

            if ($shipid) {
                $cannons = $this->getCannonsByShipId($shipid, $enemy_id);
                $result[] = $cannons;
            }

        }

        $result2 = [];

        for ($i = 0; $i < count($result); ++$i) {

            foreach ($result[$i] as $res)
            {
                $result2[] = $res;
            }
        }

        $result3 = [];

        foreach ($result2 as $res2)
        {
            for($i = 0; $i < $res2["active_quantity"]; ++$i) {

                if(self::fire_chance($res2)){
                    $fire_result = self::fire_result($res2);
                    $res2["fire_result_name"] = $fire_result["fire_result_name"];
                    $res2["fire_result_type_name"] = $fire_result["fire_result_type_name"];
                    $res2["fire_result_type"] = $fire_result["fire_result_type"];
                    $res2["fire_result"] = $fire_result["fire_result"];
                    $res2["fire_result_side"] = $fire_result["fire_result_side"];
                    self::fire_exec($res2);
                    $result3[] = $res2;
                }
            }
        }
        return $result3;
    }

    public function ai_list()
    {
        $enemy_force_id = implode(",", $this->getBattleForces()["enemy_force_id"]);

        $connection = $this->db;
        $query_enemy = "SELECT id FROM ships WHERE force_id IN ($enemy_force_id)";
        $enemy_ships = $connection->prepare($query_enemy);
        $enemy_ships->setFetchMode(PDO::FETCH_ASSOC);
        $enemy_ships->execute();
        $result = [];

        foreach ($enemy_ships as $key => $enemy_ship) {
            $result[] = $enemy_ship;
        }

        return $result;
    }

    public function ship_strength($force_id)
    {

    }



    public function max_ship_strength($force_id)
    {
        $force_id = implode(",", $force_id);
        $connection = $this->db;
        $query = "SELECT s.*, a.* FROM ships s LEFT JOIN armour a on a.shipid = s.id  WHERE s.force_id IN ($force_id) AND s.isactive = 1 AND s.inaction = 1";
        $force_ships = $connection->prepare($query);
        $force_ships->setFetchMode(PDO::FETCH_ASSOC);
        $force_ships->execute();
        $result = [];

        foreach ($force_ships as $key => $force_ship) {
            $k_armour = 1;

            if ($force_ship["armour_type"] == "garvey") {
                $k_armour = self::GARVEY_ARMOUR;
            }

            if($force_ship["armour_type"] == "krupp"){
                $k_armour = self::KRUPP_ARMOUR;
            }

            $force_ship["effective_armour"] = $force_ship["belt"] * $k_armour;

            $shipid = $force_ship["id"];

            $result["ship_strength"][$shipid]["strength"] = (int)$force_ship["displacement"] + (int)$force_ship["speed"] * 100 + (int)$force_ship["effective_armour"] * 10;
            $result["ship_strength"][$shipid]["shipid"] = $force_ship["id"];

            $query = "SELECT c.*, s.id FROM cannons c INNER JOIN ships s ON s.id = c.shipid WHERE c.shipid = :shipid";
            $cannons = $connection->prepare($query);
            $cannons->setFetchMode(PDO::FETCH_ASSOC);
            $cannons->execute(array("shipid" => $shipid));

            foreach($cannons as $cannon){
                $result["ship_strength"][$shipid]["strength"] += pow($cannon["caliber"], 2) * $cannon["quantity"] * $cannon["barrel_length"] / 10;
            }
        }

        $strength_arr = [];

        foreach ($result["ship_strength"] as $key => $res)
        {
            $strength_arr[] = $res["strength"];
        }

        $max = max($strength_arr);

        foreach ($result["ship_strength"] as $key => $res)
        {
            if($res["strength"] == $max){
                return $key;
            }
        }

        return false;
    }


    public function fire_result($item_fire)
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
            $fire_type = self::fire_type();
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

    public function fire_type()
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

    public function fire_exec($item_fire)
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

    public function fire_chance($item_fire)
    {
        $caliber_penalty = (2 * (int)$item_fire["caliber"]) / 12;
        $barrel_length_penalty = (50 -(int)$item_fire["barrel_length"]) / 5;
        $rand = rand(1, 10);
        $chance = 10 / ($barrel_length_penalty * $caliber_penalty);

        if($chance > $rand){
            return true;
        } else {
            return false;
        }
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

        $ship_flooding = $result["flooding"];

        $result["fact_speed"] = self::getFactSpeed($result["speed"], $ship_flooding);
        $k_armour = 1;

        if ($result["armour_type"] == "garvey") {
            $k_armour = self::GARVEY_ARMOUR;
        }

        if($result["armour_type"] == "krupp"){
            $k_armour = self::KRUPP_ARMOUR;
        }

        $result["effective_armour"] = round($result["belt"] * $k_armour, 2);

        return $result;
    }

    public function getFactSpeed($speed, $ship_flooding)
    {
        $k_flooding = (100 - $ship_flooding) / 100;
        $result = ceil((int)$speed * $k_flooding);
        return $result;
    }

    public function getCannonsByShipId($shipid, $enemy_id) {
        $ship = $this->getShipById($shipid);
        $ship_fires = $ship["fires"];
        $k_fires = (100 - $ship_fires) / 100;

        $connection = $this->db;
        $query = "SELECT c.*, s.name, s.country FROM cannons c INNER JOIN ships s ON s.id = c.shipid WHERE c.shipid = :shipid";
        $cannons = $connection->prepare($query);
        $cannons->setFetchMode(PDO::FETCH_ASSOC);
        $cannons->execute(array("shipid" => $shipid));
        $result = [];

        foreach ($cannons as $key => $cannon) {
           $result[$key] = $cannon;
           $result[$key]["active_quantity"] = ceil((int)$cannon["quantity"] * $k_fires);

           if ($enemy_id) {
               $result[$key]["enemy_id"] = $enemy_id;
               $result[$key]["enemy_name"] = $this->getShipById($enemy_id)["name"];
               $result[$key]["enemy_ship_length"] = $this->getShipById($enemy_id)["length"];
           }
        }

        return $result;
    }



}
?>