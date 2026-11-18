<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->query('q');
        $bookings = Booking::query()
            ->when($q, fn ($query) => $query->where(function ($query) use ($q) {
                $query->where('event_name', 'like', '%'.$q.'%')
                      ->orWhere('organizer', 'like', '%'.$q.'%');
            }))
            ->orderByDesc('event_date')
            ->paginate(8)
            ->withQueryString();

        return view('bookings.index', compact('bookings', 'q'));
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        return view('bookings.show', compact('booking'));
    }

    public function create(): View
    {
        Gate::authorize('create', Booking::class);

        return view('bookings.create', ['booking' => new Booking]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Booking::class);

        $validated = $this->validatedBooking($request);

        if (! $request->user()->isAdmin()) {
            $validated['status'] = 'upcoming';
            $validated['current_count'] = 0;
        }

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('bookings', 'public');
        }

        $booking = new Booking($validated);
        $booking->user_id = $request->user()->id;
        $booking->save();
        return to_route('bookings.index')->with('success', 'Booking created successfully.');
    }

    public function edit(Booking $booking): View
    {
        Gate::authorize('update', $booking);

        return view('bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('update', $booking);

        $validated = $this->validatedBooking($request, $booking->id);

        if (! $request->user()->isAdmin()) {
            unset($validated['status'], $validated['current_count']);
        }

        if ($request->hasFile('image')) {
            if ($booking->image_path) {
                Storage::disk('public')->delete($booking->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('bookings', 'public');
        }

        $booking->update($validated);

        return to_route('bookings.index')->with('success', 'Booking updated successfully.');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        Gate::authorize('delete', $booking);

        if ($booking->image_path) {
            Storage::disk('public')->delete($booking->image_path);
        }

        $booking->delete();

        return to_route('bookings.index')->with('success', 'Booking deleted successfully.');
    }

    private function validatedBooking(Request $request, ?int $excludeId = null): array
    {
        $rules = [
            'event_name' => ['required', 'string', 'max:255'],
            'organizer' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
            'event_date' => ['required', 'date'],
            'expected_attendees' => [
                'required',
                'integer',
                'min:1',
                'max:'.config('amphitheater.capacity'),
            ],
        ];

        if ($request->user()?->isAdmin()) {
            $rules['status'] = [
                'required',
                'in:upcoming,ongoing,completed',
                function (string $attr, mixed $value, \Closure $fail) use ($excludeId) {
                    if ($value === 'ongoing' && Booking::where('status', 'ongoing')->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))->exists()) {
                        $fail('There is already an ongoing event. Only one booking can be ongoing at a time.');
                    }
                },
            ];
            $rules['current_count'] = ['required', 'integer', 'min:0'];
        }

        return $request->validate($rules, [
            'expected_attendees.max' => 'Expected attendees cannot exceed the amphitheatre capacity of :max.',
        ]);
    }
}
