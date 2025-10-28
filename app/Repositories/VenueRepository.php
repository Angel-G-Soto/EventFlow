<?php

namespace App\Repositories;

class VenueRepository
{
  /**
   * Returns an array of all venues in the CSV file.
   * The array is in the format of the Venue model.
   * If the file does not exist or is not readable, an empty array is returned.
   * The CSV file is expected to have the following columns:
   * - room code
   * - department name
   * - name
   * - capacity
   * - email
   * - allow teaching online
   * - allow teaching with multimedia
   * - allow teaching with computer
   * - allow teaching
   * The columns can have spaces, so the function will try to find the correct column.
   * If a column is not found, it will be skipped.
   * The "name" column is used as the name of the venue.
   * If the "name" column is not found, the "room code" column is used as the name of the venue.
   * The "manager" column is used as the email of the venue.
   * If the "manager" column is not found, an empty string is used as the email.
   * The "features" column is an array of strings containing the features of the venue.
   * The "timeRanges" column is an empty array, as there are no time ranges in the CSV file.
   * @return array
   */
  public static function all(): array
  {
    $path = function_exists('base_path') ? base_path('rum_building_data4-_1_.csv') : __DIR__ . '/../../rum_building_data4-_1_.csv';
    if (!is_file($path) || !is_readable($path)) {
      return [];
    }

    $result = [];
    if (($fh = fopen($path, 'r')) === false) {
      return [];
    }

    $header = fgetcsv($fh);
    if ($header === false) {
      fclose($fh);
      return [];
    }

    // Normalize headers: trim spaces and lower-case
    $norm = array_map(function ($h) {
      return strtolower(trim((string)$h));
    }, $header);

    // Helper to get value by column name (case-insensitive, trimmed)
    $idx = function (string $name) use ($norm) {
      $name = strtolower(trim($name));
      $pos = array_search($name, $norm, true);
      return $pos === false ? null : $pos;
    };

    $iRoomCode   = $idx('room code');
    $iDeptName   = $idx('department name');
    $iName       = $idx('name'); // column labeled " Name"
    if ($iName === null) $iName = $idx(' name');
    $iCapacity   = $idx('capacity');
    if ($iCapacity === null) $iCapacity = $idx(' capacity');
    $iEmail      = $idx('email');
    if ($iEmail === null) $iEmail = $idx(' email');

    // Feature columns (handle typos/spaces)
    $iTeachOnline = $idx('allow teaching online') ?? $idx(' allow teaching online');
    $iTeachMM     = $idx('allow teaching with multimedia') ?? $idx(' allow teaching with multimedia');
    $iTeachComp   = $idx('allow teaching with computer') ?? $idx(' allow teaching with computer') ?? $idx('allow teaching wiht computer') ?? $idx(' allow teaching wiht computer');
    $iTeach       = $idx('allow teaching') ?? $idx(' allow teaching');

    $id = 1;
    while (($row = fgetcsv($fh)) !== false) {
      // Skip empty lines
      if ($row === [null] || count($row) === 0) continue;

      $get = function ($index) use ($row) {
        if ($index === null) return '';
        return isset($row[$index]) ? trim((string)$row[$index]) : '';
      };

      $name       = $get($iName);
      $room       = $get($iRoomCode);
      $department = $get($iDeptName);
      $capacity   = (int) $get($iCapacity);
      $email      = $get($iEmail);

      // Build features from flags == '1'
      $features = [];
      if ($get($iTeachOnline) === '1') $features[] = 'Allow Teaching Online';
      if ($get($iTeachMM) === '1')     $features[] = 'Allow Teaching With Multimedia';
      if ($get($iTeachComp) === '1')   $features[] = 'Allow Teaching with computer';
      if ($get($iTeach) === '1')       $features[] = 'Allow Teaching';

      // Only include rows with at least a room code and a name
      if ($room === '' && $name === '') {
        continue;
      }

      $result[] = [
        'id'         => $id++,
        'name'       => $name !== '' ? $name : $room,
        'room'       => $room,
        'capacity'   => $capacity,
        'department' => $department,
        // UI shows 'manager' as username/email; place email here
        'manager'    => $email,
        'status'     => 'Active',
        'features'   => $features,
        'timeRanges' => [], // none in CSV; can be edited via UI later
      ];
    }

    fclose($fh);
    return $result;
  }
}
