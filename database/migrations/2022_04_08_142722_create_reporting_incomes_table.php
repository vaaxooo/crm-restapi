<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportingIncomesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reporting_incomes', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('comment')->nullable();
            $table->string('manager_bio');
            $table->string('manager_id');
            $table->string('total_amount');
            $table->string('payout')->nullable();
            $table->string('salary');
            $table->float('percent');
            $table->string('role')->default('manager');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reporting_incomes');
    }
}
