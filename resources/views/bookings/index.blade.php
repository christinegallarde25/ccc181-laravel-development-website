@use('App\Models\Booking')
@extends('layouts.main')

@section('title', 'Bookings')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">Bookings</h1>
            <p class="text-secondary mb-0">Manage events at Anahaw Amphitheatre.</p>
        </div>
        @can('create', Booking::class)
            <a class="btn btn-primary" href="{{ route('bookings.create') }}">New Booking</a>
        @endcan
    </div>

    <form class="mb-3" method="GET" action="{{ route('bookings.index') }}">
        <div class="input-group" style="max-width:400px;">
            <input class="form-control" type="search" name="q" value="{{ $q }}" placeholder="Search event or organizer…">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col"></th>
                        <th scope="col">Event</th>
                        <th scope="col">Organizer</th>
                        <th scope="col">Date</th>
                        <th scope="col">Expected</th>
                        <th scope="col">Status</th>
                        <th scope="col">Current count</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>
                                @if ($booking->image_path)
                                    <img src="{{ Storage::url($booking->image_path) }}" alt="" class="rounded" style="width:48px;height:48px;object-fit:cover;">
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $booking->event_name }}</td>
                            <td>{{ $booking->organizer }}</td>
                            <td>{{ $booking->event_date->format('M j, Y') }}</td>
                            <td>{{ number_format($booking->expected_attendees) }}</td>
                            <td><span class="badge text-bg-{{ $booking->status === 'ongoing' ? 'success' : ($booking->status === 'completed' ? 'secondary' : 'primary') }}">{{ ucfirst($booking->status) }}</span></td>
                            <td>{{ number_format($booking->current_count) }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('bookings.show', $booking) }}">View</a>
                                @can('update', $booking)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('bookings.edit', $booking) }}">Edit</a>
                                @endcan
                                @can('delete', $booking)
                                    <form class="d-inline" method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm('Delete this booking?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-secondary py-4" colspan="8">No bookings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $bookings->links() }}</div>
@endsection
