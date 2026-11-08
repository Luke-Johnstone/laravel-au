<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use EastpointLabs\LaravelAu\Casts\AsAbn;
use EastpointLabs\LaravelAu\Casts\AsAcn;
use EastpointLabs\LaravelAu\Casts\AsBsb;
use EastpointLabs\LaravelAu\Casts\AsPhoneNumber;
use EastpointLabs\LaravelAu\Casts\AsPostcode;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use EastpointLabs\LaravelAu\Support\Bsb;
use EastpointLabs\LaravelAu\Support\PhoneNumber;
use EastpointLabs\LaravelAu\Support\Postcode;
use EastpointLabs\LaravelAu\Support\State;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\Database\Factories\SupplierFactory;

/**
 * A business the demo app buys from, stored with the package's casts.
 *
 * @property int $id
 * @property string $name
 * @property Abn $abn
 * @property ?Acn $acn
 * @property ?Bsb $bsb
 * @property ?PhoneNumber $phone
 * @property ?PhoneNumber $mobile
 * @property string $street_line_1
 * @property ?string $street_line_2
 * @property string $suburb
 * @property State $state
 * @property Postcode $postcode
 */
#[UseFactory(SupplierFactory::class)]
final class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abn' => AsAbn::class,
            'acn' => AsAcn::class,
            'bsb' => AsBsb::class,
            'phone' => AsPhoneNumber::class,
            'mobile' => AsPhoneNumber::class,
            'state' => State::class,
            'postcode' => AsPostcode::class,
        ];
    }
}
