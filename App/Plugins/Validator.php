<?php

namespace App\Plugins;

/**
 * Validator class for objects.
 * 
 * @author: Shano Nithoer
 * @date:   2022-06-26
 * @version: 1.0.0 
 * @license: MIT
 */
class Validator {
    private object $data;
    private array $properties;
    private string $reason;

    public function __construct(object $data, array $properties) 
    {
        $this->data = $data;
        $this->properties = $properties;
    }
    
    /**
     * Function to validate the object properties.
     * 
     * @param object $data - The data to validate
     * @param array $properties - The properties to validate
     * @return bool|string - Returns true if all properties are valid or the name of the property that is invalid
     */
    public function validate(): bool | string
    {
        foreach($this->properties as $property)
        {
            if (!property_exists($this->data, $property)){
                $this->reason = $property;
                return false;
            }
        }	

        return true;
    }

    /**
     * Function to get the reason for the invalidation.
     * 
     * @return string - The name of the property that is invalid
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}