<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('student_name');
            $table->string('student_id');
            $table->string('program');
            $table->string('honors')->nullable();
            $table->string('gwa')->nullable();
            $table->date('date_conferred');
            $table->text('payload');                  // canonical JSON that was hashed (includes the salt)
            $table->string('hash', 66)->unique();     // what lives on the blockchain
            $table->string('tx_hash', 66)->nullable();
            $table->unsignedBigInteger('block_number')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();
            $table->string('revoke_tx_hash', 66)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
