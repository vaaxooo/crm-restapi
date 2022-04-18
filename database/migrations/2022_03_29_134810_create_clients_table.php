<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('surname')->nullable();
            $table->string('fullname')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default("Не прозвонен");
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('timezone')->nullable();
            $table->string('age')->nullable();
            $table->string('additional_field1')->nullable();
            $table->string('additional_field2')->nullable();
            $table->string('additional_field3')->nullable();
            $table->string('information')->nullable();
            $table->boolean('processed')->default(false);
            $table->integer('database')->nullable();
            $table->timestamps();

            $table->index(['fullname', 'phone', 'database']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clients');
    }
}
