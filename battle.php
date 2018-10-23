<?php
include $_SERVER['DOCUMENT_ROOT']."/dbconnect.php";
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

    public function __construct()
    {
        $db = new Database();
        $this->db = $db;
    }

    public function getBattleForces()
    {
       $result["rus_force_id"] = 12;
       $result["jap_force_id"] = 21;

       return $result;
    }

    public function getSides()
    {
        $result["player"] = 'russia';
        $result["enemy"] = 'japan';

        return $result;
    }

    public function initShips()
    {
        $connection = $this->db;
        $rus_force_id = $this->getBattleForces()["rus_force_id"];
        $jap_force_id = $this->getBattleForces()["jap_force_id"];

        $query_rus = "SELECT s.*, a.* FROM ships s LEFT JOIN s_armour2 a on a.shipid = s.id  WHERE s.country='russia' AND s.isactive=1 AND s.inaction=1 AND s.force_id=:rus_force_id ORDER BY s.order_id";
        $rus_ships = $connection->prepare($query_rus);
        $rus_ships->setFetchMode(PDO::FETCH_ASSOC);
        $rus_ships->execute(array("rus_force_id" => $rus_force_id));

        $query_jap = "SELECT s.*, a.* FROM ships s LEFT JOIN s_armour2 a on a.shipid = s.id  WHERE s.country='japan' AND s.isactive=1 AND s.inaction=1 AND s.force_id=:jap_force_id ORDER BY s.order_id";
        $jap_ships = $connection->prepare($query_jap);
        $jap_ships->setFetchMode(PDO::FETCH_ASSOC);
        $jap_ships->execute(array("jap_force_id" => $jap_force_id));

        $result = [];

        foreach ($rus_ships as $key => $rus_ship) {

            if((int)$rus_ship["fires"] > 0){
                $repare = 2 * (round((int)$rus_ship["crew"] / 100)) - 1;
                self::fireRepare($rus_ship["id"], $repare);
            }

            if(((int)$rus_ship["fires"] >= 100) || ((int)$rus_ship["flooding"] >= 100)) {
                self::checkDamage($rus_ship["id"]);
            }

            $result["rus_ships"][$key] = $rus_ship;
            $result["rus_ships"][$key]["fires_line"] = self::fires_line($rus_ship["id"]);
            $result["rus_ships"][$key]["flooding_line"] = self::flooding_line($rus_ship["id"]);
            $result["rus_ships"][$key]["crew_line"] = self::crew_line($rus_ship["id"]);
            $result["rus_ships"][$key]["enemy_list"] = self::enemyList($rus_ship["id"], self::getSides()["enemy"]);
            $result["rus_ships"][$key]["exit_button"] = self::exitButton($rus_ship, $jap_force_id);
        }

        foreach ($jap_ships as $key => $jap_ship) {

            if((int)$jap_ship["fires"] > 0) {
                $repare = 2 * (round((int)$jap_ship["crew"] / 100)) - 1;
                self::fireRepare($jap_ship["id"], $repare);
            }

            if((int)$jap_ship["fires"] >= 100 || (int)$jap_ship["flooding"] >= 100) {
                self::checkDamage($jap_ship["id"]);
            }

            $result["jap_ships"][$key] = $jap_ship;
            $result["jap_ships"][$key]["fires_line"] = self::fires_line($jap_ship["id"]);
            $result["jap_ships"][$key]["flooding_line"] = self::flooding_line($jap_ship["id"]);
            $result["jap_ships"][$key]["crew_line"] = self::crew_line($jap_ship["id"]);
            $result["jap_ships"][$key]["enemy_list"] = self::enemyList($jap_ship["id"], self::getSides()["player"]);
            $result["jap_ships"][$key]["exit_button"] = self::exitButton($jap_ship, $rus_force_id);
        }

        $result["jap_ships_speed"] = self::minSpeed($jap_force_id);
        $result["rus_ships_speed"] = self::minSpeed($rus_force_id);

        return $result;
    }

    public function exitButton($ship, $enemy_force_id)
    {
        $disabled = true;
        $shipid = $ship["id"];

        if ($ship["speed"] > self::minSpeed($enemy_force_id)) {
            $disabled = false;
        }

        if ($disabled) {
            $result = "<button disabled='disabled'>Выход из боя</button>";
        } else {
            $result = "<button onclick='exitShip($shipid)'>Выход из боя</button>";
        }

        return $result;
    }

    public function exitShip($shipid)
    {
        $connection = $this->db;
        $query = "UPDATE ships SET inaction=0 WHERE id=:shipid";
        $result_query = $connection->prepare($query);

        return $result_query->execute(array("shipid" => $shipid));
    }

    public function minSpeed($force_id)
    {
        $result = [];
        $connection = $this->db;
        $query = "SELECT s.* FROM ships s WHERE s.force_id=:force_id AND s.isactive=1 AND s.inaction=1 ORDER BY s.order_id";
        $ships = $connection->prepare($query);
        $ships->setFetchMode(PDO::FETCH_ASSOC);
        $ships->execute(array("force_id" => $force_id));

         foreach($ships as $key => $ship){
                $result[$key] = self::getFactSpeed($ship["speed"], $ship["flooding"]);
        }

        $min_speed = min($result);
        return $min_speed;
    }

    public function checkDamage($shipid)
    {
        $connection = $this->db;
        $query = "UPDATE ships SET isactive=0 WHERE id=:shipid";
        $result_query = $connection->prepare($query);
        $result_query->execute(array("shipid" => $shipid));
    }

    public function fireRepare($shipid, $repare)
    {
        $connection = $this->db;
        $query = "UPDATE ships SET fires=fires-:repare WHERE id=:shipid";
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

      for($i = 0; $i < count($result); ++$i) {

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
                self::fire_exec($res2);
                $result3[] = $res2;
            }
        }
    }

      return $result3;

    }

    public function ai_list()
    {

        $jap_force_id = $this->getBattleForces()["jap_force_id"];

        $connection = $this->db;
        $query_jap = "SELECT id FROM ships WHERE force_id=:jap_force_id";
        $jap_ships = $connection->prepare($query_jap);
        $jap_ships->setFetchMode(PDO::FETCH_ASSOC);
        $jap_ships->execute(array("jap_force_id" => $jap_force_id));
        $result = [];

        foreach ($jap_ships as $key => $jap_ship) {
            $result[] = $jap_ship;
        }

        return $result;
    }

    public function ai_fire()
    {
        $result = [];
        $target_list = self::ai_list();
        $rus_force_id = $this->getBattleForces()["rus_force_id"];

        foreach ($target_list as $target) {
            $shipid = $target["id"];
            $enemy_id = self::max_ship_strength($rus_force_id);

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
                    self::fire_exec($res2);
                    $result3[] = $res2;
                }
            }
        }
        return $result3;
    }

    public function max_ship_strength($force_id)
    {
        $connection = $this->db;
        $query = "SELECT s.*, a.* FROM ships s LEFT JOIN s_armour2 a on a.shipid = s.id  WHERE s.force_id=:force_id AND s.isactive=1 AND s.inaction=1";
        $force_ships = $connection->prepare($query);
        $force_ships->setFetchMode(PDO::FETCH_ASSOC);
        $force_ships->execute(array("force_id" => $force_id));
        $result = [];

        foreach ($force_ships as $key => $force_ship) {
            $k_armour=1;

            if ($force_ship["armour_type"] == "garvey") {
                $k_armour=self::GARVEY_ARMOUR;
            }

            if($force_ship["armour_type"] == "krupp"){
                $k_armour=self::KRUPP_ARMOUR;
            }

            $force_ship["effective_armour"] = $force_ship["belt"] * $k_armour;

            $shipid = $force_ship["id"];

            $result["ship_strength"][$shipid]["strength"] = (int)$force_ship["displacement"] + (int)$force_ship["speed"] * 100 + (int)$force_ship["effective_armour"] * 10;
            $result["ship_strength"][$shipid]["shipid"] = $force_ship["id"];

            $query = "SELECT c.*, s.id FROM s_cannons c INNER JOIN ships s ON s.id = c.shipid WHERE c.shipid=:shipid";
            $cannons = $connection->prepare($query);
            $cannons->setFetchMode(PDO::FETCH_ASSOC);
            $cannons->execute(array("shipid" => $shipid));

            foreach($cannons as $cannon){
                $result["ship_strength"][$shipid]["strength"] += pow($cannon["caliber"], 2) * $cannon["quantity"] * $cannon["barrel_length"] / 10;
            }
        }

        $strength_arr=[];

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
        $precision = 90;
        $class = "";

        if ($item_fire["country"] == "russia") {
            $precision = (100 - self::RUS_PRECISION * ($item_fire["enemy_ship_length"] / 100));
            $class = "green_text";
        } else if($item_fire["country"] == "japan") {
            $precision = (100 - self::JAP_PRECISION *($item_fire["enemy_ship_length"] / 100));
            $class = "red_text";
        }

        if ($chance_rand > $precision) {
            $result["fire_result_name"] ="<span class='$class'>Попадание</span>";
            $fire_type = self::fire_type();
            $result["fire_result_type_name"] = $fire_type["fire_result_type_name"];
            $result["fire_result_type"] = $fire_type["fire_result_type"];
        } else {
            $result["fire_result_name"] ="Промах";
            $result["fire_result_type_name"] = "|";
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

           $query = "UPDATE ships SET fires=fires+:fire_level, crew=crew-:crew_level WHERE id=:shipid;";
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
            $query = "UPDATE ships SET flooding=flooding+:flooding_level WHERE id=:shipid";
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

    public function enemyList($shipid, $enemy)
    {
        $connection = $this->db;
        $query = "SELECT * FROM ships WHERE country=:enemy";
        $ships = $connection->prepare($query);
        $ships->setFetchMode(PDO::FETCH_ASSOC);
        $ships->execute(array("enemy" => $enemy));

        $result = "Цель:&nbsp;<select class='target_list' name='enemy_list' onchange='setTarget(this.options[this.selectedIndex].value, $shipid); buttonEnabled()'>";
        $result .="<option value='' selected disabled>"."Выберите цель..."."</option>";

        foreach ($ships as $key => $ship) {
            $result .="<option value=".$ship['id'].">".$ship["name"]."</option>";
        }

        $result .= "</select>";

        return $result;
    }

    public function getShipById($shipid) {
        $connection = $this->db;
        $query = "SELECT s.*, a.* FROM ships s INNER JOIN s_armour2 a ON s.id=a.shipid WHERE s.id=:shipid";
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

        $result["effective_armour"] = $result["belt"] * $k_armour;

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
        $query = "SELECT c.*, s.name, s.country FROM s_cannons c INNER JOIN ships s ON s.id = c.shipid WHERE c.shipid=:shipid";
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

    public function fires_line($shipid)
    {
        $ship = $this->getShipById($shipid);
        $level = ceil($ship["fires"] * 5 / 100);
        $result ="<p class='p_line' title='Пожары'>";

        for ($i = 1; $i <= 5; $i++) {

            if($i <= $level){
                $result .= "<span class='oval red'></span>";
            } else {
                $result .= "<span class='oval grey'></span>";
            }
        }

        $result .="</p>";

        return $result;
    }

    public function flooding_line($shipid)
    {
        $ship = $this->getShipById($shipid);
        $level = ceil($ship["flooding"] * 5 / 100);
        $result ="<p class='p_line' title='Затопления'>";

        for ($i = 1; $i <= 5; $i++) {

            if ($i <= $level) {
                $result .= "<span class='oval blue'></span>";
            } else {
                $result .= "<span class='oval grey'></span>";
            }
        }

        $result .="</p>";

        return $result;
    }

    public function crew_line($shipid)
    {
        $ship = $this->getShipById($shipid);
        $level = ceil($ship["crew"] * 5 / 100);
        $result ="<p class='p_line' title='Экипаж'>";

        for ($i = 1; $i <= 5; $i++) {

            if($i <= $level){
                $result .= "<span class='oval green'></span>";
            } else {
                $result .= "<span class='oval grey'></span>";
            }
        }

        $result .="</p>";

        return $result;
    }

//foreach($rus_ships as $rusship)
//{

//    echo "<pre>";
//        print_r($ship);
//    echo "</pre>";
    //echo $rusship["name"];
//}

}
?>