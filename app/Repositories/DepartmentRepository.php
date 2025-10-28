<?php

namespace App\Repositories;

class DepartmentRepository
{
  /**
   * Parse the campus CSV and return unique departments.
   * Fields returned:
   * - id: incremental int
   * - name: Department Name (CSV column "Department Name")
   * - code: Building code (CSV column "Bldg Code")
   * - director: Email (CSV column "Email")
   *
   * Deduplication key: strtolower(trim(name)) + '|' + strtoupper(trim(code))
   * First email encountered for a key wins.
   */
  public static function all(): array
  {
    $path = function_exists('base_path') ? base_path('rum_building_data4-_1_.csv') : __DIR__ . '/../../rum_building_data4-_1_.csv';
    if (!is_file($path) || !is_readable($path)) {
      return [];
    }

    $fh = fopen($path, 'r');
    if ($fh === false) return [];

    $header = fgetcsv($fh);
    if ($header === false) {
      fclose($fh);
      return [];
    }

    // normalize headers
    $norm = array_map(fn($h) => strtolower(trim((string)$h)), $header);
    $idx = function (string $name) use ($norm) {
      $name = strtolower(trim($name));
      $pos = array_search($name, $norm, true);
      return $pos === false ? null : $pos;
    };

    $iDeptName = $idx('department name') ?? $idx(' department') ?? $idx('department');
    $iBldgCode = $idx('bldg code') ?? $idx(' bldg code');
    $iEmail    = $idx('email') ?? $idx(' email');

    $seen = [];
    $out  = [];
    $id   = 1;

    while (($row = fgetcsv($fh)) !== false) {
      if ($row === [null] || count($row) === 0) continue;
      $get = function ($i) use ($row) {
        return $i === null ? '' : (isset($row[$i]) ? trim((string)$row[$i]) : '');
      };

      $name = $get($iDeptName);
      $code = $get($iBldgCode);
      $mail = $get($iEmail);

      if ($name === '' && $code === '') continue;

      $key = strtolower($name) . '|' . strtoupper($code);
      if (isset($seen[$key])) continue; // keep first occurrence

      $out[] = [
        'id'       => $id++,
        'name'     => $name,
        'code'     => $code,
        'director' => $mail, // director now carries the email
      ];
      $seen[$key] = true;
    }

    fclose($fh);
    return $out;
  }
}
