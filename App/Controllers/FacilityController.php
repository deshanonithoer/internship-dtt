<?php

namespace App\Controllers;

use App\Plugins\Http\Response as Status;
use App\Plugins\Http\Exceptions;

class FacilityController extends BaseController {
    public function index() {
        try {
            $result = $this->db->fetchQuery("SELECT * FROM facility");
            $result = $this->getTags($result);

            return (new Status\Ok($result))->send();
        } catch (\Exception $e) {
            return (new Exceptions\InternalServerError($e))->send();
        }
    }

    public function show($id) {
        try {
            $result = $this->db->fetchQuery("SELECT * FROM facility WHERE id = ?", [$id]);
            $result = $this->getTags($result);
            return (new Status\Ok($result))->send();
        } catch (\Exception $e) {
            return (new Exceptions\InternalServerError($e))->send();
        }
    }

    public function store() {
        $data = json_decode(file_get_contents('php://input'));
        
        try {
            // Validate the post data
            $validate = $this->validateData($data, ["name", "location_id"]);
            if ($validate !== true) return (new Exceptions\BadRequest($validate . " is required!"))->send();

            // Insert the facility
            $result = $this->db->executeQuery("INSERT INTO facility (name, location_id) VALUES (?, ?)", [$data->name, $data->location_id]); 
            $newFacilityId = $this->db->getLastInsertedId();  

            // Handle the tags
            $handleTags = $this->insertTags($data, $newFacilityId);
            if ($handleTags !== true) return (new Exceptions\InternalServerError("Something went wrong inserting the tag: " . $handleTags))->send();

            return (new Status\Ok(['completed' => $result]))->send();
        } catch (\Exception $e) {
            return (new Status\BadRequest(['message' => 'Invalid JSON']))->send();
        }
    }

    public function update($id) {
        $data = json_decode(file_get_contents('php://input'));

        try {
            // Validate the post data
            $validate = $this->validateData($data, ["name", "location_id"]);
            if ($validate !== true) return (new Exceptions\BadRequest($validate . " is required!"))->send();

            // Update the facility
            $result = $this->db->executeQuery("UPDATE facility SET name = ?, location_id = ? WHERE id = ?", [$data->name, $data->location_id, $id]);

            // Handle the tags
            $handleTags = $this->insertTags($data, $id);
            if ($handleTags !== true) return (new Exceptions\InternalServerError("Something went wrong inserting the tag: " . $handleTags))->send();

            return (new Status\Ok(['completed' => $result]))->send();
        } catch (\Exception $e) {
            return (new Status\BadRequest(['message' => 'Invalid JSON']))->send();
        }
    }

    public function destroy($id) {
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
                    $existingTag = $this->db->fetchQuery("SELECT * FROM tag WHERE name = ?", [trim($tag)]);
                    if (!$existingTag) {
                        $this->db->executeQuery("INSERT INTO tag (name) VALUES (?)", [trim($tag)]);
                        $tagId = $this->db->getLastInsertedId();
                    } else {
                        $tagId = $existingTag[0]["id"];
                    }

                    $this->db->executeQuery("INSERT INTO facilitytag (facility_id, tag_id) VALUES (?, ?)", [$facilityId, $tagId]);
                } catch (\Exception $e) {
                    $this->db->rollBack();
                    return $tag;
                }
            }

            $this->db->commit();
        }

        return true;
    }
}