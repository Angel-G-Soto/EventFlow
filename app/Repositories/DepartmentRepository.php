<?php

namespace App\Repositories;

class DepartmentRepository
{
  /**
   * Static department seed compiled from rum_building_data4-_1_.csv.
   * Fields returned:
   * - id: incremental int
   * - name: Department Name
   * - code: Building code
   * - director: Email (first encountered for the name+code pair)
   *
   * Source last synced: 2025-11-01.
   */
  public static function all(): array
  {
    // Unique (name, code) pairs with first-seen director email from the CSV
    $rows = [
      ['name' => 'COLLEGE OF BUSINESS ADMINISTRATION', 'code' => 'AE',   'director' => 'naomy.martin@upr.edu'],
      ['name' => 'DECANATO DE ESTUDIANTES',            'code' => 'CH',   'director' => 'antonio.ramos4@upr.edu'],
      ['name' => 'DECANATO DE ESTUDIANTES',            'code' => 'CE',   'director' => 'francisco.maldonado5@upr.edu'],
      ['name' => 'KINESIOLOGIA',                       'code' => 'CM',   'director' => 'margarita.fernandez1@upr.edu'],
      ['name' => 'ACTIVIDADES ATLETICAS',              'code' => 'CM',   'director' => 'sr. jose riera'],
      ['name' => 'RECTORIA',                           'code' => 'PA',   'director' => 'maria.gaud@upr.edu'],
      ['name' => 'DECANATO DE ESTUDIANTES',            'code' => 'EDDE', 'director' => 'wilson.lugo@upr.edu'],
      ['name' => 'DECANATO DE ADMINISTRACION',         'code' => 'EDIFB', 'director' => 'dario.torres@upr.edu'],
      ['name' => 'OFICINA DEL DECANO DE ADMINISTRACION', 'code' => 'DARL', 'director' => 'lucas.aviles@upr.edu'],
      ['name' => 'CENTRO DE RECURSOS PARA CIENCIAS E INGENIERIA', 'code' => 'F', 'director' => 'frederick.just@upr.edu'],
      ['name' => 'DECANATO DE ESTUDIANTES',            'code' => 'Q',    'director' => 'rene.vieta@upr.edu'],
      ['name' => 'KINESIOLOGIA',                       'code' => 'GE',   'director' => 'margarita.fernandez1@upr.edu'],
      ['name' => 'OFICINA DEL DECANO Y DIRECTOR DE CIENCIAS AGRICOLAS', 'code' => 'P', 'director' => 'gladys.gonzalez7@upr.edu'],
      ['name' => 'CENTRO DE TECNOLOGIAS DE INFORMACION (CTI)', 'code' => 'M', 'director' => 'martin.melendez@upr.edu'],
      ['name' => 'RECTORIA',                           'code' => 'MUSA', 'director' => 'zorali.deferia@upr.edu'],
      ['name' => 'COMPLEJO NATATORIO',                 'code' => 'CT',  'director' => 'margarita.fernandez1@upr.edu'],
      ['name' => 'CENTRO DE RECURSOS PARA LA EDUCACION GENERAL (CIVIS)', 'code' => 'BG', 'director' => 'd.collins@upr.edu'],
      ['name' => 'OFICINA DEL DECANO DE ARTES Y CIENCIAS', 'code' => 'C', 'director' => 'manuel.valdes@upr.ed'],
      ['name' => 'ACTIVIDADES ATLETICAS',              'code' => 'PA',   'director' => 'jose.estevez8@upr.edu'],
      // Optional pair with blank email in CSV (first seen director is empty)
      ['name' => 'KINESIOLOGIA',                       'code' => 'PS',   'director' => ''],
    ];

    // Assign incremental IDs
    $out = [];
    $id  = 1;
    foreach ($rows as $r) {
      $out[] = [
        'id'       => $id++,
        'name'     => (string)($r['name'] ?? ''),
        'code'     => (string)($r['code'] ?? ''),
        'director' => (string)($r['director'] ?? ''),
      ];
    }
    return $out;
  }
}
