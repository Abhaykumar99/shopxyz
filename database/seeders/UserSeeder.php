<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * The people the shop works with: one admin, two delivery partners and a handful
 * of customers with addresses. Customers are Google accounts and have no
 * password; staff sign in with one (ADR-004).
 */
class UserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@example.com';

    public const ADMIN_PASSWORD = 'admin-demo-password';

    public const DELIVERY_PASSWORD = 'delivery-demo';

    public function run(): void
    {
        if (app()->isProduction()) {
            Log::warning('UserSeeder holds demo passwords and is skipped in production.');

            return;
        }

        User::factory()->admin()->create([
            'name' => 'Shop owner',
            'email' => self::ADMIN_EMAIL,
            'phone' => '9876543210',
            'password' => Hash::make(self::ADMIN_PASSWORD),
        ]);

        $partners = [
            ['Rahul Kumar', '9000011111', 'rahul.kumar@example.com'],
            ['Imran Ansari', '9000022222', 'imran.ansari@example.com'],
        ];

        foreach ($partners as [$name, $phone, $email]) {
            User::factory()->deliveryPartner()->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make(self::DELIVERY_PASSWORD),
            ]);
        }

        $this->seedCustomers();
    }

    private function seedCustomers(): void
    {
        $priya = User::factory()->googleCustomer()->create([
            'name' => 'Priya Sharma',
            'email' => 'priya.sharma@example.com',
            'phone' => '9830012345',
        ]);

        Address::create([
            'user_id' => $priya->id,
            'label' => 'Home',
            'recipient_name' => 'Priya Sharma',
            'phone' => '9830012345',
            'line1' => 'Flat 3B, Shanti Apartments',
            'line2' => 'Boring Road',
            'landmark' => 'Near Pani Tanki',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_default' => true,
        ]);

        Address::create([
            'user_id' => $priya->id,
            'label' => 'Work',
            'recipient_name' => 'Priya Sharma',
            'phone' => '9830012345',
            'line1' => 'Sharma Sweets and Gifts, Shop 12',
            'line2' => 'Bakerganj Market',
            'landmark' => 'Opposite the bus stand',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800004',
        ]);

        $others = [
            ['Anil Verma', '9876501234', 'House 22, Lane 4', 'Rajendra Nagar', '800016'],
            ['Sunita Devi', '9812345678', 'Shop 12, Bakerganj Market', null, '800004'],
            ['Mohit Raj', '9701122334', 'Flat 8C, Ganga Heights', 'Kankarbagh', '800020'],
            ['Kavita Singh', '9988776655', 'Flat 2A, Lotus Residency', 'Patliputra Colony', '800013'],
            ['Deepak Jha', '9911223344', 'Hotel Gulmohar, Fraser Road', null, '800001'],
            ['Ravi Ranjan', '9001122334', 'House 5, Sector 3', 'Digha', '800011'],
        ];

        foreach ($others as [$name, $phone, $line1, $line2, $pincode]) {
            $customer = User::factory()->googleCustomer()->create([
                'name' => $name,
                'email' => str($name)->lower()->replace(' ', '.')->append('@example.com')->value(),
                'phone' => $phone,
            ]);

            Address::create([
                'user_id' => $customer->id,
                'label' => 'Home',
                'recipient_name' => $name,
                'phone' => $phone,
                'line1' => $line1,
                'line2' => $line2,
                'city' => 'Patna',
                'state' => 'Bihar',
                'pincode' => $pincode,
                'is_default' => true,
            ]);
        }
    }
}
