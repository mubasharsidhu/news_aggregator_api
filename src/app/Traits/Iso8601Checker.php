<?php

namespace App\Traits;

trait Iso8601Checker
{
    /**
     * Check if a string-date is in ISO 8601 format.
     *
     * @param string $date
     * @return bool
     */
    function isIso8601Date(string $date): bool
    {
        try {
            $parsedDate = new \DateTime($date);
            // Convert back to ISO 8601 and compare to ensure valid format
            return $parsedDate->format(\DateTime::ATOM) === $date;
        } catch (\Exception $e) {
            return false;
        }
    }
}
