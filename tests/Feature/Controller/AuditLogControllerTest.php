<?php

/**
 * Feature tests for the AuditLogController@index method.
 *
 * These tests focus on verifying:
 *  - Authorization call via Gate::authorize
 *  - Correct normalization/validation of filters
 *  - Delegation to AuditService with the right parameters
 *  - Returning a View contract (without requiring a real Blade file)
 *
 * We deliberately avoid rendering any Blade templates by faking the View factory
 * to return a mock that implements Illuminate\Contracts\View\View.
 */

use App\Http\Controllers\AuditLogController;
use App\Services\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
//use Mockery;

/**
 * Create a real, empty paginator so the controller can pass it straight through.
 * This keeps the test realistic without touching the database.
 *
 * @param  int  $perPage
 * @return LengthAwarePaginator
 */
function emptyPaginator(int $perPage = 25): LengthAwarePaginator {
    return new Paginator(
        items: Collection::make([]), // no records
        total: 0,                    // zero total
        perPage: $perPage,
        currentPage: 1,
        options: ['path' => '/admin/audit-log']
    );
}

beforeEach(function () {
    // 1) Stub policy/Gate authorization so it doesn't block our tests.
    Gate::shouldReceive('authorize')
        ->with('view', 'audit-log')
        ->andReturnTrue();

    // 2) Fake the View factory so no Blade file is required.
    //    We also assert the controller passes the expected view name and data keys.
    View::shouldReceive('make')->andReturnUsing(function ($view, $data = []) {
        expect($view)->toBe('admin.audit-log.index');
        expect($data)->toHaveKeys(['logs', 'users', 'filters', 'perPage']);

        // Return a mock that implements the View contract.
        return Mockery::mock(ViewContract::class);
    });
});

// If you prefer to explicitly close Mockery after each test (Laravel usually handles this)
afterEach(function () {
    Mockery::close();
});

it('calls AuditService with default filters and returns a View contract', function () {
    // Arrange: mock AuditService to assert the expected calls.
    $service = Mockery::mock(AuditService::class);

    // Expect defaults: empty filters, perPage = 25
    $service->shouldReceive('getPaginatedLogs')
        ->once()
        ->with([
            'user_id'   => null,
            'action'    => null,
            'date_from' => null,
            'date_to'   => null,
        ], 25)
        ->andReturn(emptyPaginator(25));

    $service->shouldReceive('getAuditedUsers')
        ->once()
        ->andReturn([/* user_id => name */]);

    // System under test
    $controller = new AuditLogController($service);

    // Act: invoke the controller method directly (faster & more focused than hitting the router)
    $request = Request::create('/admin/audit-log', 'GET', []);
    $result  = $controller->index($request);

    // Assert: returns something implementing the View contract (our mocked View)
    expect($result)->toBeInstanceOf(ViewContract::class);
});

it('passes through filters and custom per_page to AuditService', function () {
    // Arrange
    $service = Mockery::mock(AuditService::class);

    // Simulate incoming query string with all filters set
    $input = [
        'user_id'   => '42',
        'action'    => 'USER_DELETED',
        'date_from' => '2025-01-01',
        'date_to'   => '2025-12-31',
        'per_page'  => '100',
    ];

    // Expectation: controller should normalize types and pass exactly these values
    $service->shouldReceive('getPaginatedLogs')
        ->once()
        ->with([
            'user_id'   => 42,
            'action'    => 'USER_DELETED',
            'date_from' => '2025-01-01',
            'date_to'   => '2025-12-31',
        ], 100)
        ->andReturn(emptyPaginator(100));

    $service->shouldReceive('getAuditedUsers')
        ->once()
        ->andReturn([42 => 'Jane Admin']);

    $controller = new AuditLogController($service);

    // Act
    $request = Request::create('/admin/audit-log', 'GET', $input);
    $result  = $controller->index($request);

    // Assert
    expect($result)->toBeInstanceOf(ViewContract::class);
});
