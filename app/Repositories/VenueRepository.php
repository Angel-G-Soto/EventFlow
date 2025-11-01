<?php

namespace App\Repositories;

use App\Models\Venue as VenueModel;
use App\Models\Department as DepartmentModel;
use App\Models\User as UserModel;

class VenueRepository
{
  /**
   * Returns an array of venues generated via factories (no DB writes).
   * Shape matches the Venues UI expectations:
   * - id: int
   * - name: string
   * - room: string (venue code)
   * - capacity: int
   * - department: string (department name)
   * - manager: string (email/username)
   * - status: 'Active'|'Inactive' (default Active)
   * - features: string[]
   * - timeRanges: array<int,array{from:string,to:string,reason?:string}>
   *
   * @return array<int,array<string,mixed>>
   */
  public static function all(): array
  {
    // Generate a small stable pool of departments and managers to pick from
    $depRaw = DepartmentModel::factory()->count(6)->raw();
    $depRows = is_array($depRaw) && isset($depRaw[0]) ? $depRaw : [$depRaw];
    $depPool = array_values(array_filter(array_map(function ($d) {
      return is_array($d) ? (string)($d['name'] ?? '') : '';
    }, $depRows), fn($n) => $n !== ''));
    if (empty($depPool)) {
      $depPool = ['General'];
    }

    $mgrRaw = UserModel::factory()->count(8)->raw([
      // avoid resolving relations when using raw
      'department_id' => fake()->numberBetween(1, 99),
    ]);
    $mgrRows = is_array($mgrRaw) && isset($mgrRaw[0]) ? $mgrRaw : [$mgrRaw];
    $mgrPool = array_values(array_filter(array_map(function ($u) {
      return is_array($u) ? (string)($u['email'] ?? '') : '';
    }, $mgrRows), fn($e) => $e !== ''));
    if (empty($mgrPool)) {
      $mgrPool = ['manager@example.com'];
    }

    // Generate venues without touching the DB
    $count = 40;
    $raw = VenueModel::factory()->count($count)->raw([
      // Override relation keys to simple scalars (not factories) for raw arrays
      'department_id' => fake()->numberBetween(1, 99),
      'manager_id'    => fake()->numberBetween(1, 999),
      // Times are not used directly in the UI list
    ]);
    $rows = is_array($raw) && isset($raw[0]) ? $raw : [$raw];

    $featureCatalog = [
      'Allow Teaching Online',
      'Allow Teaching With Multimedia',
      'Allow Teaching with computer',
      'Allow Teaching',
    ];

    $result = [];
    foreach (array_values($rows) as $i => $r) {
      $name = (string)($r['name'] ?? 'Untitled');
      $code = (string)($r['code'] ?? 'R' . (100 + $i));
      $cap  = (int)($r['capacity'] ?? 0);
      $open = $r['opening_time'] ?? null;
      $close = $r['closing_time'] ?? null;

      // Format opening/closing times to HH:MM for display
      if ($open instanceof \DateTimeInterface) {
        $open = $open->format('H:i');
      } else {
        $open = is_string($open) ? substr($open, 0, 5) : '';
      }
      if ($close instanceof \DateTimeInterface) {
        $close = $close->format('H:i');
      } else {
        $close = is_string($close) ? substr($close, 0, 5) : '';
      }

      // pick department and manager from the generated pools
      $dep = $depPool[array_rand($depPool)] ?? 'General';
      $mgr = $mgrPool[array_rand($mgrPool)] ?? '';

      // random subset of features
      $pick = fn() => (bool)random_int(0, 1);
      $features = array_values(array_filter($featureCatalog, fn($f) => $pick()));

      $result[] = [
        'id'         => 1 + $i,
        'name'       => $name,
        'room'       => $code,
        'capacity'   => $cap,
        'department' => $dep,
        'manager'    => $mgr,
        'status'     => 'Active',
        'features'   => $features,
        'timeRanges' => [],
        'opening'    => $open,
        'closing'    => $close,
      ];
    }

    return $result;
  }
}
