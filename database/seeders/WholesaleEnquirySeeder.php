<?php

namespace Database\Seeders;

use App\Enums\WholesaleEnquiryStatus;
use App\Models\User;
use App\Models\WholesaleEnquiry;
use Illuminate\Database\Seeder;

/**
 * A few quote requests for the wholesale desk (ADR-019).
 */
class WholesaleEnquirySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $enquiries = [
            [
                'reference' => 'WQ-5101',
                'business_name' => 'Sharma Sweets and Gifts',
                'contact_name' => 'Ravi Sharma',
                'phone' => '9830011111',
                'business_type' => 'retail',
                'gstin' => '10ABCDE1234F1Z5',
                'city' => 'Patna',
                'pincode' => '800004',
                'message' => '200 sweet boxes for a wedding on 12 December, with printed name cards and our logo on the sleeve.',
                'status' => WholesaleEnquiryStatus::New,
                'needed_by' => now()->addWeeks(3),
                'estimate_paise' => 1794900,
                'items' => [
                    ['sku' => 'MG-KK-3', 'name' => 'Kaju katli with silver leaf', 'quantity' => 20, 'unit_paise' => 88000],
                ],
                'days_ago' => 0,
            ],
            [
                'reference' => 'WQ-5100',
                'business_name' => 'Hotel Gulmohar',
                'contact_name' => 'Deepak Jha',
                'phone' => '9911223344',
                'business_type' => 'hospitality',
                'city' => 'Patna',
                'pincode' => '800001',
                'message' => 'Monthly welcome sweets for guest rooms, about 300 boxes a month. Can you hold a fixed price for six months?',
                'status' => WholesaleEnquiryStatus::Quoted,
                'estimate_paise' => 2988000,
                'days_ago' => 2,
            ],
            [
                'reference' => 'WQ-5099',
                'business_name' => 'Bharat Textiles',
                'contact_name' => 'Anita Kumari',
                'phone' => '9876512345',
                'business_type' => 'corporate',
                'city' => 'Patna',
                'pincode' => '800013',
                'message' => 'Diwali hampers for 150 staff with a printed greeting card from the director.',
                'status' => WholesaleEnquiryStatus::Won,
                'estimate_paise' => 17985000,
                'days_ago' => 9,
            ],
            [
                'reference' => 'WQ-5098',
                'business_name' => 'Rani Events',
                'contact_name' => 'Pooja Rani',
                'phone' => '9801122334',
                'business_type' => 'events',
                'city' => 'Patna',
                'pincode' => '800020',
                'message' => 'Return gifts for a 400-guest reception. Needed a lower price than the slab, we could not agree.',
                'status' => WholesaleEnquiryStatus::Lost,
                'estimate_paise' => 8000000,
                'days_ago' => 16,
            ],
        ];

        foreach ($enquiries as $enquiry) {
            $daysAgo = $enquiry['days_ago'];
            unset($enquiry['days_ago']);

            WholesaleEnquiry::create([
                ...$enquiry,
                'handled_by' => $enquiry['status'] === WholesaleEnquiryStatus::New ? null : $admin?->id,
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ]);
        }
    }
}
