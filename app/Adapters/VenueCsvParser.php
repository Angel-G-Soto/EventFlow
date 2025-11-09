<?php

namespace App\Adapters;

use Exception;
use InvalidArgumentException;

/**
 * VenueCsvParser Adapter
 *
 * This class is responsible for parsing the specific 'rum_building_data.csv' file
 * and translating its contents into a structured array that matches the Venue model.
 * It isolates the core application from the specific format and column names of the source CSV.
 */
class VenueCsvParser
{
    /**
     * Parses the given building data CSV and maps its rows to an array of venue data.
     *
     * @param string $filePath The absolute path to the CSV file.
     * @return array An array of associative arrays, where each inner array represents a venue.
     * @throws InvalidArgumentException if the file does not exist or is not readable.
     * @throws Exception if the CSV is malformed or required headers are missing.
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException("File does not exist or is not readable at path: {$filePath}");
        }

        $venues = [];

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new Exception("Failed to open file for reading: {$filePath}");
        }

        // Read the header row to get column indexes
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new Exception("Could not read header row from CSV file or file is empty.");
        }

        // Flip the header array to easily look up column indexes by name
        $headerMap = array_flip(array_map('trim', $header));

        // Verify that all essential columns exist
        // Note: department code comes from 'department' in the CSV, while department name is 'department_name'.
        $requiredColumns = ['name', 'room_code', 'department_name', 'department', 'capacity', 'final_exams_capacity'];
        foreach ($requiredColumns as $column) {
            if (!isset($headerMap[$column])) {
                fclose($handle);
                throw new Exception("Required CSV column '{$column}' is missing.");
            }
        }

        // Process each data row in the CSV
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty lines
            if ($row === null || $row === false) {
                continue;
            }
            $row = array_map(static fn($v) => is_string($v) ? trim($v) : $v, $row);

            // Detect and skip a second human-readable header row
            $maybeName = (string) ($row[$headerMap['name']] ?? '');
            $maybeCode = (string) ($row[$headerMap['room_code']] ?? '');
            $maybeCap  = (string) ($row[$headerMap['capacity']] ?? '');
            if (
                (strcasecmp($maybeName, 'name') === 0 && strcasecmp($maybeCode, 'room code') === 0)
                || strcasecmp($maybeCap, 'capacity') === 0
            ) {
                continue;
            }
            $mainCapacity = (int) ($row[$headerMap['capacity']] ?? 0);
            $finalExamsCapacity = (int) ($row[$headerMap['final_exams_capacity']] ?? 0);
            $venueData = [
                'v_name' => $maybeName,
                'v_code' => $maybeCode,
                // Pass the raw department name for the service to map to a local department ID.
                'department_name_raw' => (string) ($row[$headerMap['department_name']] ?? ''),
                // Capture the department code from the 'department' column
                'department_code_raw' => (string) ($row[$headerMap['department']] ?? ''),
                'v_features' => $this->buildFeaturesString($row, $headerMap),
                'v_features_code' => $this->buildFeaturesCode($row, $headerMap),
                'v_capacity' => $mainCapacity,
                // Use final exams capacity if available, otherwise fall back to main capacity
                'v_test_capacity' => $finalExamsCapacity > 0 ? $finalExamsCapacity : $mainCapacity,
            ];

            // Only add the row if it has a valid name and code
            if (!empty($venueData['v_name']) && !empty($venueData['v_code'])) {
                $venues[] = $venueData;
            }
        }

        fclose($handle);

        return $venues;
    }

    /**
     * Constructs a human-readable features string from the boolean-like columns.
     *
     * @param array $row The current CSV row.
     * @param array $headerMap The map of header names to column indexes.
     * @return string A comma-separated list of features.
     */
    private function buildFeaturesString(array $row, array $headerMap): string
    {
        $features = [];

        if (isset($headerMap['allow_teaching_with_multimedia']) && $row[$headerMap['allow_teaching_with_multimedia']] == '1') {
            $features[] = 'Multimedia Enabled';
        }
        if (isset($headerMap['allow_teaching_with_computers']) && $row[$headerMap['allow_teaching_with_computers']] == '1') {
            $features[] = 'Computers Available';
        }
        if (isset($headerMap['allow_teaching_online']) && $row[$headerMap['allow_teaching_online']] == '1') {
            $features[] = 'Online Teaching Ready';
        }
        if (isset($headerMap['allow_teaching']) && $row[$headerMap['allow_teaching']] == '1') {
            $features[] = 'Teaching Ready';
        }

        return implode(', ', $features);
    }

    /**
     * Constructs a binary feature code from the boolean-like columns.
     *
     * @param array $row The current CSV row.
     * @param array $headerMap The map of header names to column indexes.
     * @return string A binary string representing the features.
     */
    private function buildFeaturesCode(array $row, array $headerMap): string
    {
        $code = '';
        $featureColumns = [
            'allow_teaching_with_multimedia',
            'allow_teaching_with_computers',
            'allow_teaching',
            'allow_teaching_online',
        ];

        foreach ($featureColumns as $column) {
            $code .= (isset($headerMap[$column]) && trim($row[$headerMap[$column]]) == '1') ? '1' : '0';
        }

        return $code;
    }
}
