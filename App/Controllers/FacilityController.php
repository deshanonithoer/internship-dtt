<?php

namespace App\Controllers;

use App\Plugins\Http\Response as Status;
use App\Plugins\Http\Exceptions;

class FacilityController extends BaseController {
    /**
     * Function to retrieve all facilities
     * @return \App\Plugins\Http\Response
     */
    public function index() {
        try {
            $search = [
                "location" => "city", 
                "facility" => "name",
                "tag" => "name"
            ];
            
            $query = "";
            $params = [];

            // Build the query
            foreach($search as $key => $column){
                if (isset($_GET[$key])) {
                    $query .= " AND " . $key . "." . $column . " LIKE ?";
                    $params[] = "%" . filter_var($_GET[$key], FILTER_SANITIZE_FULL_SPECIAL_CHARS) . "%";
                } 
            }

            $result = $this->db->fetchQuery("
                SELECT 
                    facility.*,
                    location.city 
                FROM facility
                LEFT JOIN location ON facility.location_id = location.id
                LEFT JOIN facilitytag ON facility.id = facilitytag.facility_id
                LEFT JOIN tag ON facilitytag.tag_id = tag.id
                WHERE 1 = 1 " . $query . "
                GROUP BY facility.id"
                , $params);

            $result = $this->getTags($result);

            return (new Status\Ok($result))->send();
        } catch (\Exception $e) {
            return (new Exceptions\InternalServerError($e))->send();
        }
    }

    /**
     * Function to retrieve a facility by id
     * @param int $id
     * @return \App\Plugins\Http\Response
     */
    public function show(int $id) {
        try {
            $result = $this->db->fetchQuery("SELECT * FROM facility WHERE id = ?", [$id]);
            $result = $this->getTags($result);
            return (new Status\Ok($result))->send();
        } catch (\Exception $e) {
            return (new Exceptions\InternalServerError($e))->send();
        }
    }

    /**
     * Function to create a new facility with the corresponding tags
     * @param array $data
     * @return \App\Plugins\Http\Response
     */
    public function store() {
        $data = json_decode(file_get_contents('php://input'));
        
        try {
            // Validate the post data
            $validate = $this->validateData($data, ["name", "location"]);
            if ($validate !== true) return (new Exceptions\BadRequest($validate . " is required!"))->send();

            // Insert the location if needed
            $location = $this->insertLocation($data);

            // Check if the facility already exists
            $result = $this->db->fetchQuery("SELECT * FROM facility WHERE name = ? AND location_id = ?", [$data->name, $location]);
            if (count($result) > 0) return (new Status\BadRequest("Facility already exists!"))->send();

            // Insert the facility
            $result = $this->db->executeQuery("INSERT INTO facility (name, location_id) VALUES (?, ?)", [$data->name, $location]); 
            $newFacilityId = $this->db->getLastInsertedId();  

            // Handle the tags
            $handleTags = $this->insertTags($data, $newFacilityId);
            if ($handleTags !== true) return (new Exceptions\InternalServerError("Something went wrong inserting the tag: " . $handleTags))->send();

            return (new Status\Ok(['completed' => $result]))->send();
        } catch (\Exception $e) {
            return (new Status\BadRequest(['message' => 'Invalid JSON']))->send();
        }
    }

    /**
     * Function to update a facility by id
     * @param int $id
     * @param array $data
     * @return \App\Plugins\Http\Response
     */
    public function update(int $id) {
        $data = json_decode(file_get_contents('php://input'));

        try {
            // Validate the post data
            $validate = $this->validateData($data, ["name", "location_id"]);
            if ($validate !== true) return (new Exceptions\BadRequest($validate . " is required!"))->send();

            // Insert the location if needed
            $location = $this->insertLocation($data);

            // Update the facility
            $result = $this->db->executeQuery("UPDATE facility SET name = ?, location_id = ? WHERE id = ?", [$data->name, $location, $id]);

            // Handle the tags
            $handleTags = $this->insertTags($data, $id);
            if ($handleTags !== true) return (new Exceptions\InternalServerError("Something went wrong inserting the tag: " . $handleTags))->send();

            return (new Status\Ok(['completed' => $result]))->send();
        } catch (\Exception $e) {
            return (new Status\BadRequest(['message' => 'Invalid JSON']))->send();
        }
    }

    /**
     * Function to delete a facility by id
     * @param int $id
     * @return \App\Plugins\Http\Response
     */
    public function destroy(int $id) {
        try {
            $result = $this->db->executeQuery("DELETE FROM facility WHERE id = ?", [$id]);
            return (new Status\Ok(['completed' => $result]))->send();
        } catch (\Exception $e) {
            return (new Status\BadRequest(['message' => 'Invalid JSON']))->send();
        }
    }

    /** HELPERS */

    /**
     * Function to get the tags for each facility
     * @param array $result
     * @return array
     */
    private function getTags(array $facilities){
        if (count($facilities)) {
            // Add the corresponding tags to the facilities
            foreach($facilities as $key => $facility) {
                $facilities[$key]["tags"] = $this->db->fetchQuery("
                    SELECT 
                        tag.*
                    FROM facilitytag
                    RIGHT JOIN tag ON facilitytag.tag_id = tag.id
                    WHERE facilitytag.facility_id = ?
                ", [$facility["id"]]);
            }
        }

        return $facilities;
    }

    /**
     * Function to validate the values in the received data
     * @param object $data
     * @param array $properties
     * @return bool | string
     */
    private function validateData(object $data, array $properties): bool|string {
        foreach($properties as $property){
            if (!property_exists($data, $property)){
                return $property;
            }
        }	

        return true;
    }

    /**
     * Function to insert the tags for each facility
     * @param object $data
     * @param int $id
     * @return bool | string
     */
    private function insertTags(object $data, int $facilityId): bool|string {
        if (property_exists($data, "tags")) {
            $this->db->beginTransaction();

            // First delete all the tags for this facility
            $this->db->executeQuery("DELETE FROM facilitytag WHERE facility_id = ?", [$facilityId]);

            foreach($data->tags as $tag){
                try {
                    // Check if the tag already exists
                    $existingTag = $this->db->fetchQuery("SELECT * FROM tag WHERE name = ?", [trim($tag)]);
                    if (!$existingTag) {
                        // Insert the tag if it does not exists
                        $this->db->executeQuery("INSERT INTO tag (name) VALUES (?)", [trim($tag)]);
                        $tagId = $this->db->getLastInsertedId();
                    } else {
                        $tagId = $existingTag[0]["id"];
                    }

                    // Check if row exists
                    $existingRow = $this->db->fetchQuery("SELECT * FROM facilitytag WHERE facility_id = ? AND tag_id = ?", [$facilityId, $tagId]);
                    if (count($existingRow) == 0) $this->db->executeQuery("INSERT INTO facilitytag (facility_id, tag_id) VALUES (?, ?)", [$facilityId, $tagId]);
                } catch (\Exception $e) {
                    $this->db->rollBack();
                    return $tag;
                }
            }

            $this->db->commit();
        }

        return true;
    }

    /**
     * Function to insert the location if needed
     * @param object $data
     * @return int
     */
    private function insertLocation(object $data, bool $update = false): bool|int {
        if (property_exists($data, "location")) {
            $validate = $this->validateData($data->location, ["city", "country", "address", "zip_code", "phone_number"]);
            if ($validate !== true) return (new Exceptions\BadRequest($validate . " is required for the creation of a new location!"))->send();

            // Check if location exists
            $existingLocation = $this->db->fetchQuery("
                SELECT * FROM location 
                WHERE LOWER(city) LIKE ? AND LOWER(address) LIKE ? AND LOWER(zip_code) LIKE ? 
                AND LOWER(country) LIKE ? AND LOWER(phone_number) LIKE ?", [
                    strtolower($data->location->city), 
                    strtolower($data->location->address),
                    strtolower($data->location->zip_code),
                    strtolower($data->location->country),
                    strtolower($data->location->phone_number)
                ]);

            // If the location doens't exist, create it
            if (count($existingLocation) == 0) {
                $this->db->executeQuery("INSERT INTO location (city, address, zip_code, country, phone_number) VALUES (?, ?, ?, ?, ?)", [
                    $data->location->city, 
                    $data->location->address,
                    $data->location->zip_code,
                    $data->location->country,
                    $data->location->phone_number
                ]);

                return $this->db->getLastInsertedId();
            } else {
                return $existingLocation[0]["id"];
            }
        }

        return true;
    }
}