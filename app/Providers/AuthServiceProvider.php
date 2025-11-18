<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\EventHistory;
use App\Policies\AdminPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EventHistoryPolicy;
use App\Policies\EventPolicy;
use App\Policies\VenuePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [

    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // My Requests
        Gate::define('viewMyRequest', [EventPolicy::class, 'viewMyRequest']);

        // Pending Requests to Approve
        Gate::define('viewMyPendingRequests', [EventPolicy::class, 'viewMyPendingRequests']);
        Gate::define('manageMyPendingRequests', [EventPolicy::class, 'manageMyPendingRequests']);
        Gate::define('downloadEventPdf', [EventPolicy::class, 'downloadEventPdf']);

        // History of Approval
        Gate::define('viewMyApprovalHistory', [EventHistoryPolicy::class, 'viewMyApprovalHistory']);
        Gate::define('manageMyApprovalHistory', [EventHistoryPolicy::class, 'manageMyApprovalHistory']);

        // Venue Management
        Gate::define('view-venue', [VenuePolicy::class, 'view']);
        Gate::define('update-requirements', [VenuePolicy::class, 'updateRequirements']);
        Gate::define('update-availability', [VenuePolicy::class, 'updateAvailability']);

        // Department
        Gate::define('view-department', [DepartmentPolicy::class, 'view']);
        Gate::define('assign-manager', [DepartmentPolicy::class, 'assignManager']);

        // System Administration
        Gate::define('access-dashboard', [AdminPolicy::class, 'accessDashboard']);
        Gate::define('perform-override', [AdminPolicy::class, 'performOverride']);
        Gate::define('manage-users', [AdminPolicy::class, 'manageUsers']);
        Gate::define('manage-venues', [AdminPolicy::class, 'manageVenues']);
        Gate::define('manage-categories', [AdminPolicy::class, 'manageCategories']);
    }
}
