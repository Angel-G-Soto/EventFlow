<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventHistory;
use App\Models\Role;
use App\Models\User;
Use App\Models\Venue;
use Carbon\Carbon;
use DateTime;
use Doctrine\DBAL\Query;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use PHPUnit\Event\EventCollection;
use Illuminate\Database\Eloquent\Collection;
use function PHPUnit\Framework\isEmpty;

class EventHistoryService
{

    // Injected services
    //protected $eventHistoryService;
    protected $venueService;
    protected $categoryService;

    // Construct
    public function __construct(VenueService $venueService, CategoryService $categoryService)
    {
        $this->venueService = $venueService;
        $this->categoryService = $categoryService;
    }

    // Methods

    public function genericApproverRequestHistoryV2(User $user, ?array $roles = []): \Illuminate\Database\Eloquent\Builder
    {
        $activeRoles = !empty($roles)
            ? $roles
            : $user->roles->pluck('name')->toArray();

        $query = EventHistory::where('approver_id', $user->id)
            ->whereIn('action', ['approved', 'rejected', 'cancelled'])
            ->where(function ($q) use ($activeRoles, $user) {
                foreach ($activeRoles as $role) {
                    $q->orWhere(function ($sub) use ($role, $user) {
                        switch ($role) {
                            case 'advisor':
                                    $sub->where('status_when_signed', 'pending - advisor approval');
                                break;

                            case 'venue-manager':
                                    $sub->where('status_when_signed', 'pending - venue manager approval');
                                break;

                            case 'event-approver':
                                $sub->where('status_when_signed', 'pending - dsca approval');
                                break;

//                            case 'deanship-of-administration-approver':
//                                $sub->where('status_when_signed', 'pending - deanship of administration approval');
//                                break;
                        }
                    });
                }
            });

        return $query;
    }


}
