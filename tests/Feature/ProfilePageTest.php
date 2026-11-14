<?php

namespace Tests\Feature;

use App\Livewire\Pages\Dashboard\ProfilePage;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_the_profile_page(): void
    {
        $this->get('/dashboard/profile')->assertRedirect('/login');
    }

    public function test_the_profile_page_shows_the_customers_details(): void
    {
        $customer = Customer::factory()->create(['name' => 'Sok Dara', 'phone' => '012 345 678']);

        $this->actingAs($customer, 'customer')->get('/dashboard/profile')->assertOk();

        Livewire::actingAs($customer, 'customer')->test(ProfilePage::class)
            ->assertSet('name', 'Sok Dara')
            ->assertSet('email', $customer->email)
            ->assertSet('phone', '012 345 678');
    }

    public function test_a_customer_can_update_their_details(): void
    {
        $customer = Customer::factory()->create();

        Livewire::actingAs($customer, 'customer')->test(ProfilePage::class)
            ->set('name', 'Chan Sreyneang')
            ->set('email', 'sreyneang@example.com')
            ->set('phone', '098 111 222')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertSet('profileSaved', __('Your details have been saved.'));

        $customer->refresh();
        $this->assertSame('Chan Sreyneang', $customer->name);
        $this->assertSame('sreyneang@example.com', $customer->email);
        $this->assertSame('098 111 222', $customer->phone);
    }

    public function test_the_email_cannot_be_taken_from_another_account(): void
    {
        Customer::factory()->create(['email' => 'taken@example.com']);
        $customer = Customer::factory()->create();

        Livewire::actingAs($customer, 'customer')->test(ProfilePage::class)
            ->set('email', 'taken@example.com')
            ->call('saveProfile')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_keeping_your_own_email_is_allowed(): void
    {
        $customer = Customer::factory()->create();

        Livewire::actingAs($customer, 'customer')->test(ProfilePage::class)
            ->set('name', 'New Name')
            ->call('saveProfile')
            ->assertHasNoErrors();
    }

    public function test_a_customer_can_change_their_password(): void
    {
        $customer = Customer::factory()->create(['password' => Hash::make('old-password')]);

        Livewire::actingAs($customer, 'customer')->test(ProfilePage::class)
            ->set('current_password', 'old-password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('current_password', '')
            ->assertSet('password', '');

        $this->assertTrue(Hash::check('new-password', $customer->fresh()->password));
    }

    public function test_the_current_password_must_be_correct(): void
    {
        $customer = Customer::factory()->create(['password' => Hash::make('old-password')]);

        Livewire::actingAs($customer, 'customer')->test(ProfilePage::class)
            ->set('current_password', 'wrong')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $customer->fresh()->password));
    }

    public function test_the_new_password_must_be_confirmed_and_long_enough(): void
    {
        $customer = Customer::factory()->create(['password' => Hash::make('old-password')]);

        Livewire::actingAs($customer, 'customer')->test(ProfilePage::class)
            ->set('current_password', 'old-password')
            ->set('password', 'short')
            ->set('password_confirmation', 'different')
            ->call('updatePassword')
            ->assertHasErrors('password');
    }
}
