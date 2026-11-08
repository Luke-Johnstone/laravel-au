<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('abn', 11);
            $table->string('acn', 9)->nullable();
            $table->string('bsb', 6)->nullable();
            // E.164 or national digits, as set in config/au.php.
            $table->string('phone', 12)->nullable();
            $table->string('mobile', 12)->nullable();
            $table->string('street_line_1');
            $table->string('street_line_2')->nullable();
            $table->string('suburb');
            $table->string('state', 3);
            $table->string('postcode', 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
