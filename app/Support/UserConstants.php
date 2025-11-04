<?php

namespace App\Support;

class UserConstants
{
    public const ROLES = [
        'Advisor',
        'Venue Manager',
        'System Admin',
        'Department Director',
        'Deanship of Administration Approver',
        'Event Approver',
    ];

    public const DEPARTMENTS = [
        'Engineering',
        'Business',
        'Arts & Sciences',
        'Education',
        'Agriculture',
    ];

    public const ROLES_WITHOUT_DEPARTMENT = [
        // All roles except 'venue-manager' do not require department
        'Advisor',
        'System Admin',
        'Department Director',
        'Deanship of Administration Approver',
        'Event Approver',
    ];
}
