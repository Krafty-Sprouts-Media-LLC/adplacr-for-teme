<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The ad versions whose impressions and clicks may be counted right now. Teme builds a public route's
 * handler without the settings reader, so the plugin keeps this list in step with its settings itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adplacr_live', function (Blueprint $table): void {
            $table->unsignedSmallInteger('slot');
            $table->string('version', 8);
            $table->primary(['slot', 'version']);
        });
    }
};
