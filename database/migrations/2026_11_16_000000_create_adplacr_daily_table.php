<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily counts of impressions and clicks per ad slot and version. No visitor data is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adplacr_daily', function (Blueprint $table): void {
            $table->date('day');
            $table->unsignedSmallInteger('slot');
            $table->string('version', 8);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->primary(['day', 'slot', 'version']);
        });
    }
};
