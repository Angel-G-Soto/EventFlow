<?php

namespace App\Repositories;

class VenueRepository
{
  public static function all(): array
  {
    return [
      [
        'id' => 1,
        'name' => 'Auditorium A',
        'building' => 'Main',
        'room' => '101',
        'capacity' => 300,
        'department' => 'Arts',
        'manager' => 'jdoe',
        'status' => 'Active',
        'features' => [
          'Allow Teaching Online',
          'Wheelchair'
        ],
        'timeRanges' => [
          ['from' => '08:00', 'to' => '12:00'],
          ['from' => '13:00', 'to' => '17:00']
        ]
      ],
      [
        'id' => 2,
        'name' => 'Lab West',
        'building' => 'Science',
        'room' => 'B12',
        'capacity' => 32,
        'department' => 'Biology',
        'manager' => 'mruiz',
        'status' => 'Inactive',
        'features' => [
          'Allow Teaching',
          'Allow Teaching With Multimedia'
        ],
        'timeRanges' => [
          ['from' => '09:00', 'to' => '11:00']
        ]
      ],
      [
        'id' => 3,
        'name' => 'Courtyard',
        'building' => 'North',
        'room' => 'OUT',
        'capacity' => 120,
        'department' => 'Facilities',
        'manager' => 'lortiz',
        'status' => 'Active',
        'features' => [
          'Allow Teaching'
        ],
        'timeRanges' => [
          ['from' => '17:00', 'to' => '20:00']
        ]
      ],
      [
        'id' => 4,
        'name' => 'Conference Room East',
        'building' => 'Administration',
        'room' => '205',
        'capacity' => 50,
        'department' => 'Business',
        'manager' => 'agarcia',
        'status' => 'Active',
        'features' => [
          'Allow Teaching With Multimedia',
          'Allow Teaching with computer'
        ],
        'timeRanges' => [
          ['from' => '08:00', 'to' => '18:00']
        ]
      ],
      [
        'id' => 5,
        'name' => 'Computer Lab 1',
        'building' => 'Technology Center',
        'room' => 'T101',
        'capacity' => 40,
        'department' => 'Computer Science',
        'manager' => 'jsmith',
        'status' => 'Active',
        'features' => [
          'Allow Teaching with computer',
          'Allow Teaching Online',
          'Allow Teaching With Multimedia'
        ],
        'timeRanges' => [
          ['from' => '07:00', 'to' => '12:00'],
          ['from' => '13:00', 'to' => '21:00']
        ]
      ],
      [
        'id' => 6,
        'name' => 'Lecture Hall B',
        'building' => 'Main',
        'room' => '305',
        'capacity' => 200,
        'department' => 'General Studies',
        'manager' => 'cwilliams',
        'status' => 'Active',
        'features' => [
          'Allow Teaching',
          'Allow Teaching With Multimedia'
        ],
        'timeRanges' => [
          ['from' => '08:00', 'to' => '17:00']
        ]
      ],
      [
        'id' => 7,
        'name' => 'Chemistry Lab',
        'building' => 'Science',
        'room' => 'S220',
        'capacity' => 28,
        'department' => 'Chemistry',
        'manager' => 'dmartinez',
        'status' => 'Active',
        'features' => [
          'Allow Teaching'
        ],
        'timeRanges' => [
          ['from' => '08:00', 'to' => '16:00']
        ]
      ],
      [
        'id' => 8,
        'name' => 'Seminar Room',
        'building' => 'Library',
        'room' => 'L150',
        'capacity' => 25,
        'department' => 'Library Services',
        'manager' => 'rbrown',
        'status' => 'Active',
        'features' => [
          'Allow Teaching',
          'Allow Teaching With Multimedia',
          'Allow Teaching Online'
        ],
        'timeRanges' => [
          ['from' => '09:00', 'to' => '20:00']
        ]
      ],
      [
        'id' => 9,
        'name' => 'Art Studio',
        'building' => 'Arts Building',
        'room' => 'A110',
        'capacity' => 30,
        'department' => 'Fine Arts',
        'manager' => 'kjohnson',
        'status' => 'Active',
        'features' => [
          'Allow Teaching'
        ],
        'timeRanges' => [
          ['from' => '10:00', 'to' => '18:00']
        ]
      ],
      [
        'id' => 10,
        'name' => 'Engineering Workshop',
        'building' => 'Engineering',
        'room' => 'E015',
        'capacity' => 35,
        'department' => 'Mechanical Engineering',
        'manager' => 'tlee',
        'status' => 'Active',
        'features' => [
          'Allow Teaching',
          'Allow Teaching with computer'
        ],
        'timeRanges' => [
          ['from' => '08:00', 'to' => '17:00']
        ]
      ],
      [
        'id' => 11,
        'name' => 'Music Rehearsal Hall',
        'building' => 'Performing Arts',
        'room' => 'PA201',
        'capacity' => 60,
        'department' => 'Music',
        'manager' => 'mdavis',
        'status' => 'Active',
        'features' => [
          'Allow Teaching',
          'Allow Teaching With Multimedia'
        ],
        'timeRanges' => [
          ['from' => '08:00', 'to' => '22:00']
        ]
      ],
      [
        'id' => 12,
        'name' => 'Small Group Study',
        'building' => 'Library',
        'room' => 'L202',
        'capacity' => 12,
        'department' => 'Library Services',
        'manager' => 'rbrown',
        'status' => 'Inactive',
        'features' => [
          'Allow Teaching with computer'
        ],
        'timeRanges' => [
          ['from' => '08:00', 'to' => '20:00']
        ]
      ],
      [
        'id' => 13,
        'name' => 'Athletic Center Meeting Room',
        'building' => 'Sports Complex',
        'room' => 'SC105',
        'capacity' => 45,
        'department' => 'Physical Education',
        'manager' => 'pwilson',
        'status' => 'Active',
        'features' => [
          'Allow Teaching',
          'Allow Teaching With Multimedia'
        ],
        'timeRanges' => [
          ['from' => '06:00', 'to' => '22:00']
        ]
      ],
      [
        'id' => 14,
        'name' => 'Virtual Learning Center',
        'building' => 'Technology Center',
        'room' => 'T305',
        'capacity' => 20,
        'department' => 'Distance Education',
        'manager' => 'sanderson',
        'status' => 'Active',
        'features' => [
          'Allow Teaching Online',
          'Allow Teaching with computer',
          'Allow Teaching With Multimedia'
        ],
        'timeRanges' => [
          ['from' => '07:00', 'to' => '21:00']
        ]
      ],
      [
        'id' => 15,
        'name' => 'Nursing Simulation Lab',
        'building' => 'Health Sciences',
        'room' => 'HS120',
        'capacity' => 24,
        'department' => 'Nursing',
        'manager' => 'jthomas',
        'status' => 'Active',
        'features' => [
          'Allow Teaching',
          'Allow Teaching With Multimedia'
        ],
        'timeRanges' => [
          ['from' => '08:00', 'to' => '17:00']
        ]
      ],
    ];
  }
}
