<?php

namespace App\Controllers;

use App\Plugins\Http\Response as Status;
use App\Plugins\Http\Exceptions;

class FacilityController extends BaseController {
    /**
     * Function to retrieve all facilities
     * @return object Status object with the facilities in an array
     */
    public function index(): object 
    {
        $search = [
            "location" => "city", 
            "facility" => "name",
            "tag" => "name"
        ];
        
        $query = "";
        $params = [];

        /* Build the query */
        foreach($search as $key => $column){
            if (isset($_GET[$key]) === false) {
                continue;
            }

            $query .= " AND " . $key . "." . $column . " LIKE ?";
            $params[] = "%" . filter_var($_GET[$key], FILTER_SANITIZE_FULL_SPECIAL_CHARS) . "%";
        }

        $result = $this->db->fetchQuery("
            SELECT 
                facility.*,
                location.city 
            FROM facility
            LEFT JOIN location ON facility.location_id = location.id
            LEFT JOIN facilitytag ON facility.id = facilitytag.facility_id
            LEFT JOIN tag ON facilitytag.tag_id = tag.id
            WHERE 1 " . $query . "
            GROUP BY facility.id"
            , $params);

        $result = $this->getFacilityTags($result);

        $status = new Status\Ok($result);
        $status->send();
        return $status;
    }

    /**
     * Function to retrieve a facility by id
     * @param int $id
     * @return object - Boolean true if the show was successful, otherwise the error message
     */
    public function show(int $id): object 
    {
        $result = $this->db->fetchQuery("SELECT * FROM facility WHERE id = ?", [$id]);
        $result = $this->getFacilityTags($result);

        $status = new Status\Ok($result);
        $status->send();
        return $status;
    }

    /**
     * Function to create a new facility with the corresponding tags
     * @return object - Boolean true if the store was successful, otherwise the error message
     */
    public function store(): object 
    {
        /* Get the post data */
        $data = json_decode(file_get_contents('php://input')); 
        
        /* Validate the post data */
        $validate = $this->validateObject($data, ["name", "location"]);
        if ($validate !== true) return (new Exceptions\BadRequest($validate . " is required!"))->send();

        /* Insert the location if needed */
        $location = $this->insertLocation($data);

        /* Check if the facility already exists */
        $result = $this->db->fetchQuery("SELECT * FROM facility WHERE name = ? AND location_id = ?", [$data->name, $location]);
        if (count($result) > 0) return (new Status\BadRequest("Facility already exists!"))->send();

        /* Insert the facility */
        $result = $this->db->executeQuery("INSERT INTO facility (name, location_id) VALUES (?, ?)", [$data->name, $location]); 
        $newFacilityId = $this->db->getLastInsertedId();  

        /* Handle the tags */
        $handleTags = $this->insertFacilityTags($data, $newFacilityId);
        if ($handleTags !== true) return (new Exceptions\InternalServerError("Something went wrong inserting the tag: " . $handleTags))->send();

        $status = new Status\Ok(['completed' => $result]);
        $status->send();
        return $status;
    }

    /**
     * Function to update a facility by id
     * @param int $id - The id of the facility to update
     * @return object - Boolean true if the update was successful, otherwise the error message
     */
    public function update(int $id): object 
    {
        $data = json_decode(file_get_contents('php://input'));

        /* Validate the post data */
        $validate = $this->validateObject($data, ["name", "location_id"]);
        if ($validate !== true) return (new Exceptions\BadRequest($validate . " is required!"))->send();

        /* Insert the location if needed */
        $location = $this->insertLocation($data);

        /* Update the facility */
        $result = $this->db->executeQuery("UPDATE facility SET name = ?, location_id = ? WHERE id = ?", [$data->name, $location, $id]);

        /* Handle the tags */
        $handleTags = $this->insertFacilityTags($data, $id);
        if ($handleTags !== true) return (new Exceptions\InternalServerError("Something went wrong inserting the tag: " . $handleTags))->send();

        
        $status = new Status\Ok(['completed' => $result]);
        $status->send();
        return $status;
    }

    /**
     * Function to delete a facility by id
     * @param int $id - The id of the facility to delete
     * @return object - Boolean true if the delete was successful, otherwise the error message
     */
    public function destroy(int $id): object 
    {
        $result = $this->db->executeQuery("DELETE FROM facility WHERE id = ?", [$id]);
        
        $status = new Status\Ok(['completed' => $result]);
        $status->send();
        return $status;
    }

    /** HELPERS */

    /**
     * Function to get the tags for each facility
     * @param array $result - The facilitiees from the query
     * @return array - The facilities with the tags
     */
    private function getFacilityTags(array $facilities): array
    {
        if (count($facilities)) {
            /* Add the corresponding tags to the facilities */
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
     * @param object $data - The data to validate
     * @param array $properties - The properties to validate
     * @return bool|string - Returns true if all properties are valid or the name of the property that is invalid
     */
    private function validateObject(object $data, array $properties): bool|string 
    {
        foreach($properties as $property){
            if (!property_exists($data, $property)){
                return $property;
            }
        }	

        return true;
    }

    /**
     * Function to insert the tags for each facility
     * @param object $data - The data received from the post
     * @param int $facilityId - The id of the facility
     * @return bool|string - Returns true if everything went well, otherwise returns the tag name where it went wrong
     */
    private function insertFacilityTags(object $data, int $facilityId): bool|string 
    {
        if (property_exists($data, "tags")) {
            $this->db->beginTransaction();

            /* First delete all the tags for this facility */
            $this->db->executeQuery("DELETE FROM facilitytag WHERE facility_id = ?", [$facilityId]);

            foreach($data->tags as $tag){
                /* Check if the tag already exists */
                $existingTag = $this->db->fetchQuery("SELECT * FROM tag WHERE name = ?", [trim($tag)]);
                if (!$existingTag) {
                    /* Insert the tag if it does not exists */
                    $this->db->executeQuery("INSERT INTO tag (name) VALUES (?)", [trim($tag)]);
                    $tagId = $this->db->getLastInsertedId();
                } else {
                    $tagId = $existingTag[0]["id"];
                }

                if (!$tagId) {
                    $this->db->rollBack();
                    return $tag;
                }

                /* Check if row exists */
                $existingRow = $this->db->fetchQuery("SELECT * FROM facilitytag WHERE facility_id = ? AND tag_id = ?", [$facilityId, $tagId]);
                if (count($existingRow) == 0) $this->db->executeQuery("INSERT INTO facilitytag (facility_id, tag_id) VALUES (?, ?)", [$facilityId, $tagId]);
            }

            $this->db->commit();
        }

        return true;
    }

    /**
     * Function to insert the location if needed
     * @param object $data - The data received from the client
     * @return bool|int - The id of the location
     */
    private function insertLocation(object $data): bool|int 
    {
        if (property_exists($data, "location")) {
            $validate = $this->validateObject($data->location, ["city", "country", "address", "zip_code", "phone_number"]);
            if ($validate !== true) return (new Exceptions\BadRequest($validate . " is required for the creation of a new location!"))->send();

            /* Check if location exists */
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

            /* If the location doens't exist, create it */
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