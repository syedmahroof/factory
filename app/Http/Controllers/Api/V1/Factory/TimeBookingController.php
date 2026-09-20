<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\TimeBooking;
use Illuminate\Http\Request;

class TimeBookingController extends BaseController
{
    public function index(Request $request)
    {
        $query = TimeBooking::query();
        $request->merge(['search_fields' => ['booking_date']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new TimeBooking);
        $time_booking = TimeBooking::create($validated);

        return $this->success($time_booking, 'Created', 201);
    }

    public function show(TimeBooking $time_booking)
    {
        return $this->success($time_booking);
    }

    public function update(Request $request, TimeBooking $time_booking)
    {
        $validated = $this->validatedFor($request, $time_booking, $time_booking->id);
        $time_booking->update($validated);

        return $this->success($time_booking, 'Updated');
    }

    public function destroy(TimeBooking $time_booking)
    {
        $time_booking->delete();

        return $this->success(null, 'Deleted');
    }
}
