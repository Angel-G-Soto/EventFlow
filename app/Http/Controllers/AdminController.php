<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Models\Venue;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdminController extends Controller
{
  use AuthorizesRequests;

  /**
   * Display the user management dashboard.
   *
   * @throws \Illuminate\Auth\Access\AuthorizationException
   */
  public function usersIndex(): View
  {
    $this->authorize('accessDashboard', User::class);

    $users = User::with(['roles', 'department'])->paginate(15);

    return view('admin.users.index', compact('users'));
  }

  /**
   * Display the departments management dashboard.
   *
   * @throws \Illuminate\Auth\Access\AuthorizationException
   */
  public function departmentsIndex(): View
  {
    $this->authorize('accessDashboard', User::class);

    // Using Department::employees() relation instead of users()
    $departments = Department::query()->withCount('employees')->paginate(15);

    return view('admin.departments.index', compact('departments'));
  }

  /**
   * Display the venues management dashboard.
   *
   * @throws \Illuminate\Auth\Access\AuthorizationException
   */
  public function venuesIndex(): View
  {
    $this->authorize('accessDashboard', User::class);

    // Eager load department and manager relations
    $venues = Venue::with(['department'])->paginate(15);

    return view('admin.venues.index', compact('venues'));
  }

  /**
   * Display the event oversight dashboard.
   *
   * @throws \Illuminate\Auth\Access\AuthorizationException
   */
  public function eventsIndex(): View
  {
    $this->authorize('accessDashboard', User::class);

    // Event model provides requester() and venue() relations
    $events = Event::with(['requester', 'venue'])->latest('id')->paginate(15);

    return view('admin.events.index', compact('events'));
  }

  /**
   * Display the override management dashboard.
   *
   * @throws \Illuminate\Auth\Access\AuthorizationException
   */
  public function overridesIndex(): View
  {
    $this->authorize('accessDashboard', User::class);

    // This could later include recent override history or a search form
    return view('admin.overrides.index');
  }

  /**
   * Perform an administrative override action on an event.
   *
   * @throws \Illuminate\Auth\Access\AuthorizationException
   */
  public function performOverride(Request $request): RedirectResponse
  {
    $this->authorize('performOverride', User::class);

    $validated = $request->validate([
      'event_id' => 'required|exists:events,id',
      'action'   => 'required|string|in:force-approve,revert',
      'reason'   => 'nullable|string|max:1000',
    ]);

    /** @var Event $event */
    $event = Event::query()->findOrFail($validated['event_id']);

    // Define what each override action does
    $map = [
      'force-approve' => 'approved',
      'revert'        => 'pending approval - manager',
    ];

    $event->status = $map[$validated['action']];
    $event->save();

    // (Optional) Later, you can log an EventHistory entry here.

    return redirect()
      ->route('admin.overrides.index')
      ->with('status', 'Override applied successfully.');
  }
}
