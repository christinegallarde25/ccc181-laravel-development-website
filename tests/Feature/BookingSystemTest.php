<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_the_ongoing_event_and_its_capacity_level(): void
    {
        $this->actingAs(User::factory()->create());

        Booking::create([
            'event_name' => 'Community Gathering',
            'organizer' => 'City Government',
            'event_date' => today(),
            'expected_attendees' => 6000,
            'status' => 'ongoing',
            'current_count' => 6000,
        ]);

        $this->get('/')
            ->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Community Gathering')
            ->assertSee('6,000')
            ->assertSee('High')
            ->assertSee('85.71%');
    }

    #[DataProvider('capacityLevels')]
    public function test_danger_level_matches_capacity_thresholds(int $count, string $level, float $percent): void
    {
        $booking = new Booking(['current_count' => $count]);
        $danger = $booking->dangerLevel();

        $this->assertSame($level, $danger['level']);
        $this->assertSame($percent, $danger['percent']);
    }

    public static function capacityLevels(): array
    {
        return [
            'below safe threshold' => [4199, 'Safe', 59.99],
            'at moderate threshold' => [4200, 'Moderate', 60.0],
            'below high threshold' => [5599, 'Moderate', 79.99],
            'at high threshold' => [5600, 'High', 80.0],
            'below critical threshold' => [6649, 'High', 94.99],
            'at critical threshold' => [6650, 'Critical', 95.0],
            'at capacity' => [7000, 'Critical', 100.0],
            'over capacity' => [7001, 'Over capacity', 100.01],
        ];
    }

    public function test_dashboard_count_can_be_set_and_adjusted_without_going_below_zero(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $booking = Booking::create([
            'event_name' => 'Community Gathering',
            'organizer' => 'City Government',
            'event_date' => today(),
            'expected_attendees' => 1000,
            'status' => 'ongoing',
            'current_count' => 5,
        ]);

        $this->post(route('dashboard.count'), ['current_count' => 25])
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'current_count' => 25]);

        $this->post(route('dashboard.count'), ['adjustment' => -10])
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'current_count' => 15]);

        $this->post(route('dashboard.count'), ['adjustment' => -10])
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'current_count' => 5]);

        $this->post(route('dashboard.count'), ['adjustment' => -10])
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'current_count' => 0]);
    }

    public function test_booking_creation_rejects_expected_attendance_above_capacity(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('bookings.create'))
            ->post(route('bookings.store'), [
                'event_name' => 'Oversized Event',
                'organizer' => 'City Government',
                'event_date' => today()->toDateString(),
                'expected_attendees' => 7001,
                'status' => 'upcoming',
                'current_count' => 0,
            ])
            ->assertRedirect(route('bookings.create'))
            ->assertSessionHasErrors('expected_attendees');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_dashboard_reports_when_there_is_no_ongoing_event(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertOk()->assertSee('There is no ongoing event.');
    }

    public function test_guests_are_redirected_to_login_from_bookings(): void
    {
        $this->get('/bookings')->assertRedirect(route('login'));
    }

    public function test_non_owner_gets_403_on_edit_and_delete(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $booking = new Booking([
            'event_name' => 'Owner Event',
            'organizer' => 'Owner Org',
            'event_date' => today(),
            'expected_attendees' => 500,
            'status' => 'upcoming',
            'current_count' => 0,
        ]);
        $booking->user_id = $owner->id;
        $booking->save();

        $this->actingAs($otherUser);

        $this->get(route('bookings.edit', $booking))->assertForbidden();
        $this->put(route('bookings.update', $booking), [
            'event_name' => 'Hacked Event',
            'organizer' => 'Owner Org',
            'event_date' => today()->toDateString(),
            'expected_attendees' => 500,
        ])->assertForbidden();
        $this->delete(route('bookings.destroy', $booking))->assertForbidden();
    }

    public function test_owner_can_edit_and_delete_their_booking(): void
    {
        $owner = User::factory()->create();

        $booking = new Booking([
            'event_name' => 'Owner Event',
            'organizer' => 'Owner Org',
            'event_date' => today(),
            'expected_attendees' => 500,
            'status' => 'upcoming',
            'current_count' => 0,
        ]);
        $booking->user_id = $owner->id;
        $booking->save();

        $this->actingAs($owner);

        $this->get(route('bookings.edit', $booking))->assertOk();
        $this->put(route('bookings.update', $booking), [
            'event_name' => 'Updated Event Name',
            'organizer' => 'Owner Org',
            'event_date' => today()->toDateString(),
            'expected_attendees' => 600,
        ])->assertRedirect(route('bookings.index'));

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'event_name' => 'Updated Event Name']);

        $this->delete(route('bookings.destroy', $booking))->assertRedirect(route('bookings.index'));
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_admin_can_edit_and_delete_any_booking(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $booking = new Booking([
            'event_name' => 'Owner Event',
            'organizer' => 'Owner Org',
            'event_date' => today(),
            'expected_attendees' => 500,
            'status' => 'upcoming',
            'current_count' => 0,
        ]);
        $booking->user_id = $owner->id;
        $booking->save();

        $this->actingAs($admin);

        $this->get(route('bookings.edit', $booking))->assertOk();
        $this->delete(route('bookings.destroy', $booking))->assertRedirect(route('bookings.index'));
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_store_sets_user_id_and_forces_defaults_for_non_admins(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post(route('bookings.store'), [
            'event_name' => 'User Event',
            'organizer' => 'User Org',
            'event_date' => today()->toDateString(),
            'expected_attendees' => 300,
            'status' => 'ongoing',
            'current_count' => 999,
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::where('event_name', 'User Event')->first();
        $this->assertNotNull($booking);
        $this->assertSame($user->id, $booking->user_id);
        $this->assertSame('upcoming', $booking->status);
        $this->assertSame(0, $booking->current_count);
    }

    public function test_non_admin_update_ignores_status_and_current_count(): void
    {
        $owner = User::factory()->create();

        $booking = new Booking([
            'event_name' => 'Original Event',
            'organizer' => 'Org',
            'event_date' => today(),
            'expected_attendees' => 500,
            'status' => 'upcoming',
            'current_count' => 0,
        ]);
        $booking->user_id = $owner->id;
        $booking->save();

        $this->actingAs($owner);

        $this->put(route('bookings.update', $booking), [
            'event_name' => 'Renamed Event',
            'organizer' => 'Org',
            'event_date' => today()->toDateString(),
            'expected_attendees' => 500,
            'status' => 'completed',
            'current_count' => 999,
        ])->assertRedirect(route('bookings.index'));

        $booking->refresh();
        $this->assertSame('Renamed Event', $booking->event_name);
        $this->assertSame('upcoming', $booking->status);
        $this->assertSame(0, $booking->current_count);
    }

    public function test_admin_can_set_status_and_current_count(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $this->post(route('bookings.store'), [
            'event_name' => 'Admin Event',
            'organizer' => 'City Org',
            'event_date' => today()->toDateString(),
            'expected_attendees' => 1000,
            'status' => 'ongoing',
            'current_count' => 250,
        ])->assertRedirect(route('bookings.index'));

        $booking = Booking::where('event_name', 'Admin Event')->first();
        $this->assertNotNull($booking);
        $this->assertSame('ongoing', $booking->status);
        $this->assertSame(250, $booking->current_count);

        $this->put(route('bookings.update', $booking), [
            'event_name' => 'Admin Event',
            'organizer' => 'City Org',
            'event_date' => today()->toDateString(),
            'expected_attendees' => 1000,
            'status' => 'completed',
            'current_count' => 300,
        ])->assertRedirect(route('bookings.index'));

        $booking->refresh();
        $this->assertSame('completed', $booking->status);
        $this->assertSame(300, $booking->current_count);
    }

    public function test_non_admin_cannot_access_dashboard_count_or_user_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post(route('dashboard.count'), ['current_count' => 100])->assertForbidden();
        $this->get('/users')->assertForbidden();
        $this->patch('/users/' . $user->id . '/role', ['role' => 'admin'])->assertForbidden();
    }

    public function test_admin_can_manage_users_and_cannot_change_own_role(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin);

        $this->get('/users')->assertOk()->assertSee($targetUser->name);

        $this->patch('/users/' . $admin->id . '/role', [
            'role' => 'user',
        ])->assertSessionHas('error');

        $admin->refresh();
        $this->assertSame('admin', $admin->role);

        $this->patch('/users/' . $targetUser->id . '/role', [
            'role' => 'admin',
        ])->assertSessionHas('success');

        $targetUser->refresh();
        $this->assertSame('admin', $targetUser->role);
    }
}
