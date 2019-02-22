<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTemplateItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('template_item', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('template_id')->unsigned();
            $table->string('description');
            $table->integer('urgency')->default(0);
            $table->integer('due_interval')->default(0);
            $table->string('due_unit');
            $table->integer('updated_by');
            $table->integer('created_by');
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->foreign('template_id')->references('id')->on('template')->onDelete('cascade');
        });
    
        DB::unprepared('
        CREATE TRIGGER template_item_after_update BEFORE UPDATE ON `template_item` FOR EACH ROW
            BEGIN
                SET NEW.updated_at = CURRENT_TIMESTAMP;
            END
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_item');
        DB::unprepared('DROP TRIGGER `template_item_after_update`');
    }
}
