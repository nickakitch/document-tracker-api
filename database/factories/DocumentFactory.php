<?php

namespace Database\Factories;

use App\Models\Document;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $fileNames = [
            'Service Agreement.pdf',
            'Behavior Support Plan.pdf',
            'Client Consent Form.pdf',
            'Employment Contract.pdf',
            'Confidentiality Agreement.pdf',
            'Payment Authorization.pdf',
            'Privacy Policy.pdf',
            'Terms and Conditions.pdf',
            'Medical Release Form.pdf',
            'Vendor Agreement.pdf',
            'Partnership Agreement.pdf',
            'Non-Disclosure Agreement.pdf',
            'Healthcare Proxy.pdf',
            'Model Release Form.pdf',
            'Invoice.pdf',
            'W-9 Form.pdf',
            'Purchase Order.pdf',
            'Lease Agreement.pdf',
            'Employment Application.pdf',
            'Promissory Note.pdf',
        ];

        return [
            'name' => $this->faker->randomElement($fileNames),
            'path' => $this->faker->filePath(),
            'expires_at' => Carbon::now()->addDays(rand(-90, 90)),
        ];
    }
}
