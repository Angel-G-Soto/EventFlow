<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCsvFileUpload;
use App\Models\Venue;
use http\Env\Request;
use App\Services\UserService;
use App\Services\VenueService;
use App\Policies\VenuePolicy;
use Illuminate\Support\Facades\Auth;

class VenueController extends Controller
{

//    /**
//     * Display a listing of the resource.
//     */
//    public function index()
//    {
//        // Policy
//
//        //$venues = new VenueService()->getAllVenues(['department_id' => Auth::user()->department_id]);
//
//        return view('venues.index' /*['venues' => $venues]*/);
//    }
//
//    /**
//     * Show the form for creating a new resource.
//     */
//    public function create()
//    {
//        //
//    }
//
//    /**
//     * Store a newly created resource in storage.
//     */
//    public function store(Request $request)
//    {
//        //
//    }
//
//    /**
//     * Display the specified resource.
//     */
//    public function show(int $venue_id)
//    {
//        // Policy
//
//        $venue = VenueService::getVenueById($venue_id);
//
//        return view('venues.show', ['venue' => $venue]);
//    }
//
//    /**
//     * Show the form for editing the specified resource.
//     */
//    public function edit(int $venue_id)
//    {
//        // Policy
//
//        $venue = VenueService::getVenueById($venue_id);
//
//        return view('venues.edit', ['venue' => $venue]);
//    }
//
//    /**
//     * Update the specified resource in storage.
//     */
//    public function update(Request $request, int $venue_id)
//    {
//        //
//    }
//
//    /**
//     * Remove the specified resource from storage.
//     */
//    public function destroy(int $venue_id)
//    {
//        //
//    }
//
//    public function show_requirements(int $venue_id)
//    {
//        // Policy
//
//        $venue = VenueService::getVenueById($venue_id);
//
//        $requirements = VenueService::getUseRequirements($venue_id);
//
//        return view('venues.showRequirements', ['venue' => $venue, 'requirements' => $requirements]);
//    }
//
//    public function edit_requirements(int $venue_id)
//    {
//        // Policy
//
//        $venue = VenueService::getVenueById($venue_id);
//
//        $requirements = VenueService::getUseRequirements($venue_id);
//
//        return view('venues.editRequirements', ['venue' => $venue, 'requirements' => $requirements]);
//    }
//
//    public function update_requirements(int $venue_id)
//    {
//
//    }





    /////////////////////////////////////////////////// REVAMP /////////////////////////////////////////////////////////

    ////////// GET

    public function managerVenueIndex()
    {
        // Run policy that verifies the user is the manager
        if (!Auth::user()->getRoleNames()->contains('venue-manager')) abort(403, 'Only managers can access this page.');

        // Run service that brings the respective venues
        $venues = VenueService::getAllVenues(['department_id' => Auth::user()->department()->id]);

        // Load view
        return view('venue.managerVenueIndex', ['venues' => $venues]);
    }

    public function directorVenueIndex()
    {
        // Run policy that verifies the user is the director
        if (!Auth::user()->getRoleNames()->contains('department-director')) abort(403, 'Only directors can access this page.');

        // Run service that brings the respective venues
        $venues = VenueService::getAllVenues(['department_id' => Auth::user()->department()->id]);

        // Load view
        return view('venue.directorVenueIndex', ['venues' => $venues]);
    }

    public function administratorVenueIndex()
    {
        // Run policy that verifies the user is the administrator
        if (!Auth::user()->getRoleNames()->contains('system-administrator')) abort(403, 'Only administrators can access this page.');

        // Run service that brings all the venues
        $venues = VenueService::getAllVenues();
        // Load view
        return view('venue.administratorVenueIndex', ['venues' => $venues]);
    }

    ////////// POST

    public function storeRequirements(Request $request, int $venue_id)
    {
        // Abort if the venue id is not valid
        if ($venue_id < 0) {abort(400, 'Venue id must be greater than zero.');}
        if (!VenueService::findById($venue_id)) {abort(404, 'Venue not found.');}

        // Run policy that verifies the user is manager and part of the department
        $this->authorize('updateRequirements', [Auth::user(), !VenueService::findById($venue_id)]);

        // Validate request
        $validated = $request->validate([
            'requirements' => 'required|array|min:0',
            'requirements.*.name' => 'required|string|max:255',
            'requirements.*.hyperlink' => 'required|string|max:512',
            'requirements.*.description' => 'required|string|max:512',
        ]);

        // Call service that updates requests
        VenueService::updateOrCreateVenueRequirements(VenueService::findById($venue_id),$validated['requirements'],Auth::user());

        // Reload page
        redirect('');
    }

    public function updateAvailability(Request $request, int $venue_id)
    {
        // Abort if the venue id is not valid
        if ($venue_id < 0) {abort(400, 'Venue id must be greater than zero.');}
        if (!VenueService::findById($venue_id)) {abort(404, 'Venue not found.');}

        // Run policy that verifies the user is manager and part of the department
        $this->authorize('updateAvailability', [Auth::user(), !VenueService::findById($venue_id)]);

        // Validate request
        $validated = $request->validate([
            'opening_hours' => 'required|date_format:H:i:s',
            'closing_hours' => 'required|date_format:H:i:s',
        ]);

        // Call service that updates the working hours of the venue
        VenueService::updateVenueOperatingHours(VenueService::findById($venue_id),$validated['opening_hours'],$validated['closing_hours'],Auth::user());

        // Reload page
        redirect('');
    }

    public function storeVenueManager(Request $request, int $venue_id)
    {
        // Abort if the venue id is not valid
        if ($venue_id < 0) {abort(400, 'Venue id must be greater than zero.');}
        if (!VenueService::findById($venue_id)) {abort(404, 'Venue not found.');}

        // Run policy that verifies the user is director and part of the department
        $this->authorize('assignManager', [Auth::user(), !VenueService::findById($venue_id)]);

        // Validate request
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        // Call service that updates the manager
        VenueService::assignManager(VenueService::findById($venue_id),UserService::findUserById($validated['user_id']),Auth::user());

        // Reload page
        redirect('');
    }

    public function deleteVenue(int $venue_id)
    {
        // Abort if the venue id is not valid
        if ($venue_id < 0) {abort(400, 'Venue id must be greater than zero.');}
        if (!VenueService::findById($venue_id)) {abort(404, 'Venue not found.');}

        // Run policy that verifies that the user is the admin
        $this->authorize('deleteVenue', Auth::user());

        // Call service that deletes the model
        VenueService::deactivateVenues([VenueService::findById($venue_id)],Auth::user());

        // Reload page
        redirect('');
    }

//    public function deleteSelectedVenues(Request $request)
//    {
//        // Run policy that verifies that the user is the admin
//        $this->authorize('deleteVenue', Auth::user());
//
//        // Validate request
//        $validated = request()->validate([
//            'venue_ids' => 'required|array|min:1|max:10',
//            'venue_ids.*' => 'integer|distinct|exists:venues,id',
//        ]);
//
//        // Call services that acquires models
//        $venues = VenueService::getVenuesByIds($validated['venue_ids']);
//
//        // Call service that deletes the models
//        VenueService::deactivateVenues($venues,Auth::user());
//
//        // Reload page
//        redirect('');
//    }

    public function storeVenue(Request $request)
    {
        // Run policy that verifies that the user is the admin
        $this->authorize('create', Auth::user());

        // Validate request
        // Validate request
        $validated = $request->validate([
            'manager_id'     => 'required|integer|exists:users,id',
            'department_id'  => 'required|integer|exists:departments,id',
            'name'           => 'required|string|max:255',
            'code'           => "required|string|max:50|unique",
            'features'       => 'required|string|max:4',
            'capacity'       => 'required|integer|min:0',
            'test_capacity'  => 'required|integer|min:0',
            'opening_time'   => 'nullable|date_format:H:i',
            'closing_time'   => 'nullable|date_format:H:i',
        ]);

        // Call service that creates the venue
        VenueService::createVenue($validated,Auth::user());

        // Reload page
        redirect('');
    }

    public function updateVenue(Request $request, int $venue_id)
    {
        // Run policy that verifies that the user is the admin
        $this->authorize('create', Auth::user());

        // Validate request
        $validated = $request->validate([
            'manager_id'     => 'nullable|integer|exists:users,id',
            'department_id'  => 'nullable|integer|exists:departments,id',
            'name'           => 'nullable|string|max:255',
            'code'           => "nullable|string|max:50|unique",
            'features'       => 'nullable|string|max:4',
            'capacity'       => 'nullable|integer|min:0',
            'test_capacity'  => 'nullable|integer|min:0',
            'opening_time'   => 'nullable|date_format:H:i',
            'closing_time'   => 'nullable|date_format:H:i',
        ]);

        // Call service that creates the venue
        $venue = VenueService::updateVenue(VenueService::getVenuesByIds($venue_id), $validated, Auth::user());

        // Reload page
        redirect('');
    }


    // VERIFY HOW TO PLACE THE SCAN AS A JOB, JUST LIKE IN DOCUMENTS
    public function importVenuesFromCSV(Request $request)
    {
        // Run policy that verifies that the user is the admin
        $this->authorize('create', Auth::user());

        // Validate request
        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $filename = 'venues_import_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('', $filename, 'uploads_temp');

        // SERVICE
        ProcessCsvFileUpload::dispatch($filename, Auth::id());

        // Redirect to page
        redirect();
    }
}
