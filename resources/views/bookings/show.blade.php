@extends('layouts.main')

@section('title', $booking->event_name)

@section('content')
    <div class="mb-4 d-flex justify-content-between align-items-start">
        <div>
            <h1 class="h2 mb-1">{{ $booking->event_name }}</h1>
            <p class="text-secondary mb-0">Booking details</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('bookings.index') }}">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            @if ($booking->image_path)
                <img src="{{ Storage::url($booking->image_path) }}" alt="{{ $booking->event_name }}" class="img-fluid rounded mb-4" style="max-height:320px;object-fit:cover;width:100%;">
            @endif
            <dl class="row mb-0">
                <dt class="col-sm-3">Organizer</dt>
                <dd class="col-sm-9">{{ $booking->organizer }}</dd>

                <dt class="col-sm-3">Event date</dt>
                <dd class="col-sm-9">{{ $booking->event_date->format('F j, Y') }}</dd>

                <dt class="col-sm-3">Expected attendees</dt>
                <dd class="col-sm-9">{{ number_format($booking->expected_attendees) }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9"><span class="badge text-bg-{{ $booking->status === 'ongoing' ? 'success' : ($booking->status === 'completed' ? 'secondary' : 'primary') }}">{{ ucfirst($booking->status) }}</span></dd>

                <dt class="col-sm-3">Current count</dt>
                <dd class="col-sm-9">{{ number_format($booking->current_count) }}</dd>

                @if ($booking->description)
                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9">{{ $booking->description }}</dd>
                @endif
            </dl>
            <div class="d-flex gap-2 mt-4">
                @can('update', $booking)
                    <a class="btn btn-primary" href="{{ route('bookings.edit', $booking) }}">Edit</a>
                @endcan
                @can('delete', $booking)
                    <form method="POST" action="{{ route('bookings.destroy', $booking) }}" onsubmit="return confirm('Delete this booking?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit">Delete</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
@endsection
