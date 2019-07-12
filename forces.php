<?php
require_once($_SERVER['DOCUMENT_ROOT']."/dbconnect.php");
?>
<?php

class Forces
{

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


    public function getShipList()
    {
        $connection = $this->db;

        $country = $this->getSides()["player"];

        $query = "SELECT s.*, f.force_name FROM ships s LEFT JOIN forces f on f.id = s.force_id  WHERE s.country=:country AND s.isactive=1 ORDER BY s.order_id";
        $ships = $connection->prepare($query);
        $ships->setFetchMode(PDO::FETCH_ASSOC);
        $ships->execute(array("country" => $country));

        //return $ships->fetchAll(PDO::FETCH_ASSOC);
        $forces = [];
        $forces_multiarr = [];

        foreach ($ships as $ship) {
            $forces[$ship["force_name"]][] = $ship;
        }

        foreach ($forces as $key => $value) {
            $forces_multiarr[] = ["force_name" => $key, "force_ships" => $value];
        }

        return $forces_multiarr;
    }

    public function getForcesList()
    {
        $connection = $this->db;
        $country = $this->getSides()["player"];
        $query = "SELECT f.*, r.region_name FROM forces f INNER JOIN regions r ON r.id = f.region_id WHERE f.country=:country";
        $forces = $connection->prepare($query);
        $forces->setFetchMode(PDO::FETCH_ASSOC);
        $forces->execute(array("country" => $country));

        return $forces->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRegionsList()
    {
        $connection = $this->db;
        $query = "SELECT * FROM regions";
        $regions = $connection->prepare($query);
        $regions->setFetchMode(PDO::FETCH_ASSOC);
        $regions->execute();

        return $regions->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getForcesByRegion($region_id)
    {
        $connection = $this->db;
        $country = $this->getSides()["player"];
        $query = "SELECT * FROM forces WHERE country=:country AND region_id = :region_id";
        $forces = $connection->prepare($query);
        $forces->setFetchMode(PDO::FETCH_ASSOC);
        $forces->execute(array("country" => $country, "region_id" => $region_id));

        return $forces->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRegionsWithForcesList()
    {
        $connection = $this->db;
        $query = "SELECT * FROM regions";
        $regions = $connection->prepare($query);
        $regions->setFetchMode(PDO::FETCH_ASSOC);
        $regions->execute();

        $regions_list = $regions->fetchAll(PDO::FETCH_ASSOC);
        $result = [];

        foreach ($regions_list as $region) {
            $forces = $this->getForcesByRegion($region["id"]);
            $result[] = array("region" => $region, "forces" => $forces);
        }

        return $result;
    }

    public function setShipsToForce($ships_list) {
        $connection = $this->db;
        $force_id = $ships_list->forceId;
        $shipsToForce = $ships_list->shipsToForce;
        $ships = implode(",", $shipsToForce);

        $query = "UPDATE ships SET force_id = :force_id WHERE id IN($ships)";
        $result = $connection->prepare($query);
        $result->execute(array("force_id" => $force_id));
    }

    public function createNewForce($force_name)
    {
        $connection = $this->db;
        $country = $this->getSides()["player"];
        $query = "INSERT INTO forces (force_name, country) VALUES (:force_name, :country)";
        $result = $connection->prepare($query);
        $result->execute(array("country" => $country, "force_name" => $force_name));
    }

    public function deleteForce($id)
    {
        if (!$id) throw new Exception("Не передан id отряда");

        $connection = $this->db;
        $query = "DELETE FROM forces WHERE id = :id LIMIT 1";
        $result = $connection->prepare($query);
        $result->execute(array("id" => $id));
    }

    public function updateForce($id, $force_name)
    {
        $connection = $this->db;
        $query = "UPDATE forces SET force_name = :force_name WHERE id = :id LIMIT 1";
        $result = $connection->prepare($query);
        $result->execute(array("id" => $id, "force_name" => $force_name));
    }

    public function setRegion($forses, $region_id)
    {
        if (!$region_id) throw new Exception("Не передан id региона");

        if (!$forses || count($forses) < 1) throw new Exception("Не выбраны отряды");

        $forses = implode(",", $forses);

        $connection = $this->db;
        $query = "UPDATE forces SET region_id = :region_id WHERE id IN($forses);
        UPDATE ships SET inaction = 1 WHERE force_id IN($forses);
        ";
        $result = $connection->prepare($query);
        $result->execute(array("region_id" => $region_id));
    }

    public function getAIForcesWithoutRegion()
    {
        $connection = $this->db;
        $country = $this->getSides()["enemy"];
        $query = "SELECT * FROM forces WHERE country=:country AND region_id IS NULL";
        $forces = $connection->prepare($query);
        $forces->setFetchMode(PDO::FETCH_ASSOC);
        $forces->execute(array("country" => $country));

        return $forces->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAIForcesAll()
    {
        $connection = $this->db;
        $country = $this->getSides()["enemy"];
        $query = "SELECT * FROM forces WHERE country=:country";
        $forces = $connection->prepare($query);
        $forces->setFetchMode(PDO::FETCH_ASSOC);
        $forces->execute(array("country" => $country));

        return $forces->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setRegionForAIForces()
    {
        $forces = $this->getAIForcesAll();
        $regions = $this->getRegionsList();

        if (count($forces) < 1) return false;

        foreach ($forces as $force) {
            $random_region_key = array_rand($regions);
            $random_region_id = $regions[$random_region_key]["id"];

            $connection = $this->db;
            $query = "UPDATE forces SET region_id = :region_id WHERE id = :force_id;
            UPDATE ships SET inaction = 1 WHERE force_id = :force_id";
            $result = $connection->prepare($query);
            $result->execute(array("region_id" => $random_region_id, "force_id" => $force["id"]));
        }

        return true;
    }

    public function turn()
    {
        return $this->setRegionForAIForces();
    }

}