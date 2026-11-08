<?php

declare(strict_types=1);

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\Supplier;

/**
 * Suppliers with valid Australian details from the package's Faker provider. The ABNs and ACNs
 * pass their checks but may belong to real businesses. The demo's lookups are faked unless you
 * set ABR_GUID, and its ABN links open the public ABN Lookup site, which may show such a business.
 *
 * @extends Factory<Supplier>
 */
final class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake('en_AU');
        $state = $faker->auState();

        return [
            'name' => $faker->company(),
            'abn' => $faker->abn(),
            'acn' => $faker->acn(),
            'bsb' => $faker->bsb(),
            'phone' => $faker->auLandlineNumber($state),
            'mobile' => $faker->auMobileNumber(),
            'street_line_1' => $faker->streetAddress(),
            'street_line_2' => null,
            'suburb' => $faker->city(),
            'state' => $state,
            'postcode' => $faker->auPostcode($state),
        ];
    }
}
