<?php
// app/Services/DocumentRequirementService.php
namespace App\Services;


class DocumentRequirementService
{
    /**
     * Returns the required documents for a venue.
     * Each item: key, label, required(bool), mimes(array), max_kb(int)
     *
     * @return array<int, array{key:string,label:string,required:bool,mimes:array,max_kb:int}>
     */
    public function forVenue(int $venueId): array
    {
// TODO: Replace with your own rules table/config.
// Example rules per venue id.
        return match ($venueId) {
            1 => [
                ['key' => 'advisor_approval', 'label' => "Advisor Approval Form", 'required' => true, 'mimes' => ['pdf'], 'max_kb' => 10240],
                ['key' => 'floor_plan', 'label' => "Floor Plan / Layout", 'required' => false, 'mimes' => ['pdf'], 'max_kb' => 10240],
            ],
            3 => [
                ['key' => 'sound_permit', 'label' => "Sound Permit", 'required' => true, 'mimes' => ['pdf'], 'max_kb' => 10240],
                ['key' => 'security_plan', 'label' => "Security Plan", 'required' => true, 'mimes' => ['pdf'], 'max_kb' => 10240],
                ['key' => 'advisor_approval', 'label' => "Advisor Approval Form", 'required' => true, 'mimes' => ['pdf'], 'max_kb' => 10240],

            ],
            default => [
                ['key' => 'advisor_approval', 'label' => "Advisor Approval Form", 'required' => true, 'mimes' => ['pdf'], 'max_kb' => 10240],
            ],
        };
    }
}
