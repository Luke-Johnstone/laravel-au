<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Tests\Filament\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A record for the table and infolist fixtures, holding each kind of value as the plain string
 * that's stored. CastTestRecord reads the same rows through the package's casts.
 *
 *     TestRecord::createTable();
 *     TestRecord::create(['abn' => '51824753556', 'state' => 'NSW']);
 *
 * @property int $id
 */
class TestRecord extends Model
{
    public $timestamps = false;

    protected $table = 'test_records';

    protected $guarded = [];

    /**
     * Create the records' table on the test database. Pest.php does this before each table and
     * infolist test.
     */
    public static function createTable(): void
    {
        Schema::create('test_records', function (Blueprint $table): void {
            $table->id();
            $table->string('abn')->nullable();
            $table->string('acn')->nullable();
            $table->string('bsb')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
        });
    }
}
