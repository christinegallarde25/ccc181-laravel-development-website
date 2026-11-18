@extends('layouts.main')

@section('title', 'New Booking')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">New Booking</h1>
        <p class="text-secondary mb-0">Add an event at Anahaw Amphitheatre.</p>
    </div>
    <div class="card shadow-sm">
        <div class="card-body p-4">
            @if (request()->query('img_error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    The uploaded image is too large. Maximum allowed size is <strong>10 MB</strong>. Please choose a smaller file.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <form method="POST" action="{{ route('bookings.store') }}" enctype="multipart/form-data" id="booking-form">
                @csrf
                @include('bookings._form', ['submitLabel' => 'Create booking'])
            </form>
        </div>
    </div>

    <script>
        (function () {
            const KEY = 'booking_form_draft';
            const form = document.getElementById('booking-form');
            const fields = ['event_name', 'organizer', 'description', 'event_date', 'expected_attendees', 'status', 'current_count'];

            const saved = JSON.parse(sessionStorage.getItem(KEY) || '{}');
            fields.forEach(function (name) {
                if (saved[name] === undefined) return;
                const el = form.elements[name];
                if (!el) return;
                if (el.tagName === 'SELECT') {
                    el.value = saved[name];
                } else {
                    el.value = saved[name];
                }
            });

            form.addEventListener('submit', function () {
                const draft = {};
                fields.forEach(function (name) {
                    const el = form.elements[name];
                    if (el) draft[name] = el.value;
                });
                sessionStorage.setItem(KEY, JSON.stringify(draft));
            });

            @if (!$errors->any())
                sessionStorage.removeItem(KEY);
            @endif
        })();
    </script>
@endsection

