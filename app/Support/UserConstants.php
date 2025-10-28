<?php

namespace App\Support;

class UserConstants
{
    public const ROLES = [
        'Student Org Rep',
        'Student Org Advisor',
        'Venue Manager',
        'DSCA Staff',
        'Dean of Administration',
        'Admin',
    ];

    public const DEPARTMENTS = [
        'Engineering',
        'Business',
        'Arts & Sciences',
        'Education',
        'Agriculture',
    ];

    public const ROLES_WITHOUT_DEPARTMENT = [
        'Student Org Rep',
        'Student Org Advisor',
        'DSCA Staff',
        'Dean of Administration',
        'Admin',
    ];
}
