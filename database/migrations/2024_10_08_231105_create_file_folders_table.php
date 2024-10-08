<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('files_and_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['file', 'folder']); // To differentiate between file and folder
            $table->integer('parent_id')->nullable(); // Nullable for root items
            $table->integer('_lft')->nullable(); // Left boundary for nested set
            $table->integer('_rgt')->nullable(); // Right boundary for nested set
            $table->integer('depth')->default(0); // Depth of the node in the hierarchy
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_folders');
    }
};
