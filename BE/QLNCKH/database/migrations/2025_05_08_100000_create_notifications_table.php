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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Sử dụng UUID cho ID thông báo
            $table->string('type'); // Loại thông báo, ví dụ: App\Notifications\ResearchTopicSubmittedNotification
            $table->morphs('notifiable'); // Liên kết đa hình đến người nhận (ví_dụ: User model cho admin)
            $table->text('data'); // Dữ liệu thông báo dưới dạng JSON
            $table->timestamp('read_at')->nullable(); // Thời điểm đọc thông báo
            $table->timestamps(); // created_at và updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};