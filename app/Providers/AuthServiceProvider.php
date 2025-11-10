<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\EventHistory;
use App\Policies\EventHistoryPolicy;
use App\Policies\EventPolicy;
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
    }
}
