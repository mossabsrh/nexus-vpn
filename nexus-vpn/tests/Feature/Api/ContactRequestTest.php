<?php

namespace Tests\Feature\Api;

use App\Models\ContactRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_can_send_a_team_inquiry_that_admins_can_handle(): void
    {
        $this->postJson('/api/contact', [
            'name' => 'Amel', 'email' => 'Amel@Acme.test', 'company' => 'Acme', 'seats' => 12,
            'message' => 'We need VPN access for our support team.',
        ])->assertCreated();

        $contact = ContactRequest::query()->sole();
        $this->assertSame('amel@acme.test', $contact->email);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs(User::factory()->create())->getJson('/api/admin/contact-requests')->assertForbidden();
        $this->actingAs($admin)->getJson('/api/admin/contact-requests')
            ->assertOk()->assertJsonPath('newCount', 1)->assertJsonPath('requests.0.seats', 12);
        $this->actingAs($admin)->patchJson("/api/admin/contact-requests/{$contact->id}", ['status' => 'handled'])
            ->assertOk()->assertJsonPath('request.status', 'handled');
    }

    public function test_inquiries_are_validated_and_rate_limited(): void
    {
        $this->postJson('/api/contact', ['name' => 'X', 'email' => 'not-an-email', 'message' => 'short'])
            ->assertJsonValidationErrors(['email', 'message']);

        $payload = ['name' => 'Amel', 'email' => 'amel@acme.test', 'message' => 'We need VPN access for our team.'];
        // The invalid attempt above already counts toward the 2-per-minute limit.
        $this->postJson('/api/contact', $payload)->assertCreated();
        $this->postJson('/api/contact', $payload)->assertStatus(429);
        $this->assertSame(1, ContactRequest::query()->count());
    }
}
