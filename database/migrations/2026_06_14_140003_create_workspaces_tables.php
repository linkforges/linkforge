<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->index();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->timestamps();
        });

        Schema::create('workspace_user', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['owner', 'admin', 'member', 'viewer'])->default('member');
            $table->primary(['workspace_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_user');
        Schema::dropIfExists('workspaces');
    }
};
