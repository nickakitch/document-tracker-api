<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('path');

            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('expires_at')->nullable();

            $table->timestamps();
        });
    }
};
