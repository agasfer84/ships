<?php
include $_SERVER['DOCUMENT_ROOT']."/dbconnect.php";
?>
<?php

class Ships extends Database
{

    public function initShips()
    {
        $connection = Database::connection();
        $query_rus = "SELECT s.*, c.* FROM ships s LEFT JOIN armour c on c.shipid = s.id  WHERE s.country='russia'";
        $rus_ships = $connection->query($query_rus);
        $rus_ships->setFetchMode(PDO::FETCH_ASSOC);

        $query_jap = "SELECT s.*, c.* FROM ships s LEFT JOIN armour c on c.shipid = s.id  WHERE s.country='japan'";
        $jap_ships = $connection->query($query_jap);
        $jap_ships->setFetchMode(PDO::FETCH_ASSOC);

        $result = [];
        foreach($rus_ships as $key=>$rus_ship){
            $result["rus_ships"][$key]=$rus_ship;
            $result["rus_ships"][$key]["fires_line"]=self::fires_line($rus_ship["id"]);
            $result["rus_ships"][$key]["flooding_line"]=self::flooding_line($rus_ship["id"]);
            $result["rus_ships"][$key]["crew_line"]=self::crew_line($rus_ship["id"]);
            $result["rus_ships"][$key]["enemy_list"]=self::enemyList($rus_ship["id"]);
        }

        foreach($jap_ships as $key=>$jap_ship){
            $result["jap_ships"][$key]=$jap_ship;
            $result["jap_ships"][$key]["fires_line"]=self::fires_line($jap_ship["id"]);
            $result["jap_ships"][$key]["flooding_line"]=self::flooding_line($jap_ship["id"]);
            $result["jap_ships"][$key]["crew_line"]=self::crew_line($jap_ship["id"]);

        }

        return $result;

    }

    public function fire($target_list)
    {
        $result=[];
      foreach($target_list as $target){
          $shipid = $target->ship_id;
          $enemy_id = $target->enemy_id;
          if($shipid){
              $cannons =  self::getCannonsByShipId($shipid, $enemy_id);
              $result[] = $cannons;
          }

      }
        $result2=[];
    for($i = 0; $i < count($result); ++$i) {
        foreach ($result[$i] as $res)
        {$result2[] = $res;}
    }

        $result3=[];
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

    public function fire_result($item_fire)
    {
        $chance_rand = rand(1,100);
        $precision =90;
        if($item_fire["country"]=="russia"){$precision=92;}
        else if($item_fire["country"]=="japan"){$precision=89;}

        if($chance_rand>$precision){
            $result["fire_result_name"] ="Попадание";
            $fire_type = self::fire_type();
            $result["fire_result_type_name"] = $fire_type["fire_result_type_name"];
            $result["fire_result_type"] = $fire_type["fire_result_type"];
        }
        else {
            $result["fire_result_name"] ="Промах";
            $result["fire_result_type_name"] = "|";
        }


        return $result;
    }

    public function fire_type()
    {
        $type_rand = rand(1,100);

        if($type_rand<30){$result["fire_result_type_name"] ="Бортовая броня";$result["fire_result_type"] ="belt";}
        else {$result["fire_result_type_name"] ="Надстройки";$result["fire_result_type"] ="superstructure";}

        return $result;

    }

    public function fire_exec($item_fire)
    {
        $shipid = $item_fire["enemy_id"];
        if($item_fire["fire_result_type"]=="superstructure")
       {
            $connection = Database::connection();
            $query = "UPDATE ships SET fires=fires+10 WHERE id=$shipid";
            $result_query = $connection->prepare($query);
            $result_query->execute();
       }

    }

    public function fire_chance($item_fire)
    {
        $barrel_length_penalty = 1;
        $caliber_penalty = (2*(int)$item_fire["caliber"])/12;
        if((int)$item_fire["barrel_length"]<40){$barrel_length_penalty = 2;}
        $rand = rand(1,10);
        $chance = 10/($barrel_length_penalty*$caliber_penalty);
        if($chance>$rand){return true;}
        else {return false;}
    }

    public static function enemyList($shipid)
    {
        $connection = Database::connection();
        $query = "SELECT * FROM ships WHERE country='japan'";
        $ships = $connection->query($query);
        $ships->setFetchMode(PDO::FETCH_ASSOC);



        $result = "Цель:&nbsp;<select class='target_list' name='enemy_list' onchange='setTarget(this.options[this.selectedIndex].value, $shipid); buttonEnabled()'>";
        $result .="<option value='' selected disabled>"."Выберите цель..."."</option>";
        foreach($ships as $key=>$ship){
            $result .="<option value=".$ship['id'].">".$ship["name"]."</option>";
        }

        $result .= "</select>";
        return $result;

    }

    public static function getShipById($shipid) {
        $connection = Database::connection();
        $query = "SELECT s.*, a.* FROM ships s INNER JOIN armour a ON s.id=a.shipid WHERE s.id=$shipid";
        $result_query = $connection->query($query);
        $result = $result_query->fetch(PDO::FETCH_ASSOC);

        $ship_flooding = $result["flooding"];
        $k_flooding = (100 - $ship_flooding)/100;
        $result["fact_speed"] = ceil((int)$result["speed"]*$k_flooding);

        $k_armour=1;
        if($result["armour_type"]=="garvey"){$k_armour=1.2;}
        if($result["armour_type"]=="krupp"){$k_armour=1.4;}
        $result["effective_armour"] = $result["belt"]*$k_armour;
        return $result;

    }

    public static function getCannonsByShipId($shipid, $enemy_id) {
        $ship = self::getShipById($shipid);
        $ship_fires = $ship["fires"];
        $k_fires = (100 - $ship_fires)/100;

        $connection = Database::connection();
        $query = "SELECT c.*, s.name, s.country FROM cannons c INNER JOIN ships s ON s.id = c.shipid WHERE c.shipid=$shipid";
        $cannons = $connection->query($query);
        $cannons->setFetchMode(PDO::FETCH_ASSOC);
        $result = [];
        foreach($cannons as $key=>$cannon){
           $result[$key]=$cannon;
           $result[$key]["active_quantity"] = ceil((int)$cannon["quantity"]*$k_fires);
           if($enemy_id){
               $result[$key]["enemy_id"] = $enemy_id;
               $result[$key]["enemy_name"] = self::getShipById($enemy_id)["name"];
           }
        }
        return $result;

    }

    public function fires_line($shipid)
    {
        $ship = self::getShipById($shipid);

        $level = ceil($ship["fires"]*5/100);

        $result ="<p class='p_line' title='Пожары'>";
        for ($i = 1; $i <= 5; $i++) {
            if($i<=$level){$result .= "<span class='oval red'></span>"; }
            else {$result .= "<span class='oval grey'></span>";}
        }
        $result .="</p>";
        return $result;
    }

    public function flooding_line($shipid)
    {
        $ship = self::getShipById($shipid);

        $level = ceil($ship["flooding"]*5/100);

        $result ="<p class='p_line' title='Затопления'>";
        for ($i = 1; $i <= 5; $i++) {
            if($i<=$level){$result .= "<span class='oval blue'></span>"; }
            else {$result .= "<span class='oval grey'></span>";}
        }

        $result .="</p>";
        return $result;
    }

    public function crew_line($shipid)
    {
        $ship = self::getShipById($shipid);

        $level = ceil($ship["crew"]*5/100);

        $result ="<p class='p_line' title='Экипаж'>";
        for ($i = 1; $i <= 5; $i++) {
            if($i<=$level){$result .= "<span class='oval green'></span>"; }
            else {$result .= "<span class='oval grey'></span>";}
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