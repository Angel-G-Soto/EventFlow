<?php

namespace App\Repositories;

class UserRepository
{
  public static function all(): array
  {
    return [
      ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@upr.edu', 'role' => 'DSCA Staff'],
      ['id' => 2, 'name' => 'Juan De la Cruz', 'email' => 'juan@upr.edu', 'role' => 'Student Org Rep'],
      ['id' => 3, 'name' => 'Alma Ruiz', 'email' => 'mruiz@upr.edu', 'role' => 'Admin'],
      ['id' => 4, 'name' => 'Leo Ortiz', 'email' => 'leo@upr.edu', 'role' => 'Venue Manager', 'department' => 'Arts & Sciences'],
      ['id' => 5, 'name' => 'Ana Diaz', 'email' => 'adiaz@upr.edu', 'role' => 'Student Org Advisor'],
      ['id' => 6, 'name' => 'Carlos Rivera', 'email' => 'crivera@upr.edu', 'role' => 'Student Org Rep'],
      ['id' => 7, 'name' => 'Sofia Martinez', 'email' => 'smartinez@upr.edu', 'role' => 'Venue Manager', 'department' => 'Education'],
      ['id' => 8, 'name' => 'Miguel Torres', 'email' => 'mtorres@upr.edu', 'role' => 'DSCA Staff'],
      ['id' => 9, 'name' => 'Isabella Garcia', 'email' => 'igarcia@upr.edu', 'role' => 'Student Org Advisor'],
      ['id' => 10, 'name' => 'Diego Morales', 'email' => 'dmorales@upr.edu', 'role' => 'Admin'],
      ['id' => 11, 'name' => 'Valentina Cruz', 'email' => 'vcruz@upr.edu', 'role' => 'Student Org Rep'],
      ['id' => 12, 'name' => 'Alejandro Vega', 'email' => 'avega@upr.edu', 'role' => 'Venue Manager', 'department' => 'Business'],
      ['id' => 13, 'name' => 'Camila Herrera', 'email' => 'cherrera@upr.edu', 'role' => 'DSCA Staff'],
      ['id' => 14, 'name' => 'Sebastian Luna', 'email' => 'sluna@upr.edu', 'role' => 'Student Org Advisor'],
      ['id' => 15, 'name' => 'Lucia Mendez', 'email' => 'lmendez@upr.edu', 'role' => 'Dean of Administration'],
      ['id' => 16, 'name' => 'Mateo Jimenez', 'email' => 'mjimenez@upr.edu', 'role' => 'Student Org Rep'],
      ['id' => 17, 'name' => 'Gabriela Santos', 'email' => 'gsantos@upr.edu', 'role' => 'Venue Manager', 'department' => 'Agriculture'],
      ['id' => 18, 'name' => 'Nicolas Flores', 'email' => 'nflores@upr.edu', 'role' => 'DSCA Staff'],
      ['id' => 19, 'name' => 'Antonella Ramos', 'email' => 'aramos@upr.edu', 'role' => 'Student Org Advisor'],
      ['id' => 20, 'name' => 'Emilio Castro', 'email' => 'ecastro@upr.edu', 'role' => 'Admin'],
      ['id' => 21, 'name' => 'Renata Vargas', 'email' => 'rvargas@upr.edu', 'role' => 'Student Org Rep'],
      ['id' => 22, 'name' => 'Joaquin Delgado', 'email' => 'jdelgado@upr.edu', 'role' => 'Venue Manager', 'department' => 'Arts & Sciences'],
      ['id' => 23, 'name' => 'Valeria Ortega', 'email' => 'vortega@upr.edu', 'role' => 'DSCA Staff'],
      ['id' => 24, 'name' => 'Andres Molina', 'email' => 'amolina@upr.edu', 'role' => 'Student Org Advisor'],
      ['id' => 25, 'name' => 'Martina Aguilar', 'email' => 'maguilar@upr.edu', 'role' => 'Student Org Rep'],
      ['id' => 26, 'name' => 'Fernando Reyes', 'email' => 'freyes@upr.edu', 'role' => 'Student Org Rep'],
      ['id' => 27, 'name' => 'Catalina Romero', 'email' => 'cromero@upr.edu', 'role' => 'Venue Manager', 'department' => 'Agriculture'],
    ];
  }
}
