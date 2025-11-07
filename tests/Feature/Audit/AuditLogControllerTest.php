<?php
/**
 * AuditLogControllerTest
 *
 * Purpose:
 *   Verify the controller logic WITHOUT rendering Blade or touching the DB:
 *     - Validates request & normalizes filters/per_page
 *     - Calls AuditService with the expected arguments
 *     - Returns a View contract pointing at 'admin.audit.index'
 *
 * Notes:
 *   - We mock the View factory so no Blade file is needed.
 *   - We mock AuditService to assert correct delegation.
 */

use App\Http\Controllers\AuditLogController;
use App\Services\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
//use Mockery;

/** Helper: create an empty paginator instance (no DB needed). */
function audit_empty_paginator(int $perPage = 25): LengthAwarePaginator {
    return new Paginator(
        items: Collection::make([]),
        total: 0,
        perPage: $perPage,
        currentPage: 1,
        options: ['path' => '/admin/audit'] // only used for link building
    );
}

beforeEach(function () {
    // Do not render Blade; return a View-contract mock instead.
    View::shouldReceive('make')->andReturnUsing(function ($view, $data = []) {
        expect($view)->toBe('admin.audit.index'); // matches your controller
        expect($data)->toHaveKeys(['logs', 'users', 'filters', 'perPage']);
        return Mockery::mock(ViewContract::class);
    });
});

afterEach(function () {
    Mockery::close();
});

it('calls AuditService with default filters and returns a View', function () {
    // Arrange
    $service = Mockery::mock(AuditService::class);

    // Expect default/empty filters and perPage=25
    $service->shouldReceive('getPaginatedLogs')
        ->once()
        ->with([
            'user_id'   => null,
            'action'    => null,
            'date_from' => null,
            'date_to'   => null,
        ], 25)
        ->andReturn(audit_empty_paginator(25));

    $service->shouldReceive('getAuditedUsers')
        ->once()
        ->andReturn([]);

    $controller = new AuditLogController($service);

    // Act
    $request = Request::create('/admin/audit', 'GET', []);
    $result  = $controller->index($request);

    // Assert
    expect($result)->toBeInstanceOf(ViewContract::class);
});

it('passes filters and custom per_page through to AuditService', function () {
    // Arrange
    $service = Mockery::mock(AuditService::class);

    $input = [
        'user_id'   => '99',
        'action'    => 'EVENT_CREATED',
        'date_from' => '2025-01-01',
        'date_to'   => '2025-12-31',
        'per_page'  => '100',
    ];

    // Expect normalized values (user_id int, per_page int)
    $service->shouldReceive('getPaginatedLogs')
        ->once()
        ->with([
            'user_id'   => 99,
            'action'    => 'EVENT_CREATED',
            'date_from' => '2025-01-01',
            'date_to'   => '2025-12-31',
        ], 100)
        ->andReturn(audit_empty_paginator(100));

    $service->shouldReceive('getAuditedUsers')
        ->once()
        ->andReturn([99 => 'Label']);

    $controller = new AuditLogController($service);

    // Act
    $request = Request::create('/admin/audit', 'GET', $input);
    $result  = $controller->index($request);

    // Assert
    expect($result)->toBeInstanceOf(ViewContract::class);
});
