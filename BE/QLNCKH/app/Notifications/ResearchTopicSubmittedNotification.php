<?php

namespace App\Notifications;

use App\Models\DeTai;
use App\Models\User as LecturerUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResearchTopicSubmittedNotification extends Notification implements ShouldBroadcast // Implement ShouldBroadcast
{
    use Queueable;

    public DeTai $deTai;
    public LecturerUser $lecturer;

    public function __construct(DeTai $deTai, LecturerUser $lecturer)
    {
        $this->deTai = $deTai;
        $this->lecturer = $lecturer;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast']; // Gửi qua database và broadcast (Pusher)
    }

    // Dữ liệu lưu vào database
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Giảng viên {$this->lecturer->ho_ten} ({$this->lecturer->msvc}) vừa đăng ký đề tài mới: {$this->deTai->ten_de_tai}.",
            'topic_id' => $this->deTai->id,
            'topic_name' => $this->deTai->ten_de_tai,
            'lecturer_name' => $this->lecturer->ho_ten,
            'lecturer_msvc' => $this->lecturer->msvc,
            'submitted_at' => now()->toDateTimeString(),
            'link' => route('admin.topics.show', $this->deTai->id) // Ví dụ
        ];
    }

    // Dữ liệu gửi qua Pusher (kênh private của user)
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => "Giảng viên {$this->lecturer->ho_ten} ({$this->lecturer->msvc}) vừa đăng ký đề tài mới: {$this->deTai->ten_de_tai}.",
            'topic_id' => $this->deTai->id,
            'topic_name' => $this->deTai->ten_de_tai,
            // ... các dữ liệu khác bạn muốn gửi qua Pusher
        ]);
    }

    // (Tùy chọn) Nếu bạn muốn gửi email
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //                 ->line("Giảng viên {$this->lecturer->ho_ten} vừa đăng ký đề tài: {$this->deTai->ten_de_tai}.")
    //                 ->action('Xem chi tiết', route('admin.topics.show', $this->deTai->id))
    //                 ->line('Cảm ơn bạn đã sử dụng ứng dụng!');
    // }
}
