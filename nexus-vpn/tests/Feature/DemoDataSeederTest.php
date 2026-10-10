<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\PlansTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_team_owner_is_a_member_and_sees_their_team(): void
    {
        $this->seed(PlansTableSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $team = Team::where('name', 'Sonatrach IT')->firstOrFail();
        $owner = User::findOrFail($team->owner_id);

        // The owner is attached to the membership pivot with the "owner" role, exactly like the real
        // AdminBillingController provisioning flow. Regression: the seeder used to skip this, so
        // /api/team (which reads the membership pivot) returned null for the owner and the dashboard
        // hid the very team they own.
        $pivot = $team->members()->whereKey($owner->id)->first();
        $this->assertNotNull($pivot, 'The team owner should be a member of their own team.');
        $this->assertSame('owner', $pivot->pivot->role);
        $this->assertSame(3, $team->members()->count(), 'Owner + two members = three seats used.');

        $this->actingAs($owner)
            ->getJson('/api/team')
            ->assertOk()
            ->assertJsonPath('team.name', 'Sonatrach IT')
            ->assertJsonPath('team.seatsUsed', 3);
    }
}
