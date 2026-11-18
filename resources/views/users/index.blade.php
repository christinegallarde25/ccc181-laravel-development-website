@extends('layouts.main')

@section('title', 'Manage Users')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">Manage Users</h1>
            <p class="text-secondary mb-0">Manage user accounts and roles.</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge text-bg-{{ $user->role === 'admin' ? 'danger' : 'secondary' }}">{{ $user->role }}</span></td>
                            <td class="text-end">
                                <form class="d-inline-flex gap-2 justify-content-end align-items-center" method="POST" action="{{ route('users.update-role', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select class="form-select form-select-sm w-auto" name="role" aria-label="Role for {{ $user->name }}">
                                        <option value="user" @selected($user->role === 'user')>user</option>
                                        <option value="admin" @selected($user->role === 'admin')>admin</option>
                                    </select>
                                    <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-center text-secondary py-4" colspan="4">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($users instanceof \Illuminate\Pagination\AbstractPaginator)
        <div class="mt-3">{{ $users->links() }}</div>
    @endif
@endsection

