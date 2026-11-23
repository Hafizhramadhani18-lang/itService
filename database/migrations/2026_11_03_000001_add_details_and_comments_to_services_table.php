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
        Schema::table('services', function (Blueprint $table) {
            $table->string('judul_kendala')->nullable()->after('ticket_number');
            $table->string('lampiran_foto')->nullable()->after('keluhan');
        });

        Schema::create('service_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name');
            $table->string('author_role')->default('pegawai');
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_comments');
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['judul_kendala', 'lampiran_foto']);
        });
    }
};

