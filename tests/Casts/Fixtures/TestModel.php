<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Tests\Casts\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * A model with no table, for testing casts through attribute access. Give it its casts when
 * making it, then set raw attributes or assign to them:
 *
 *     TestModel::casting(['abn' => AsAbn::class])->setRawAttributes(['abn' => '51824753556']);
 */
final class TestModel extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    /**
     * @param  array<string, string>  $casts
     */
    public static function casting(array $casts): self
    {
        return (new self)->mergeCasts($casts);
    }
}
