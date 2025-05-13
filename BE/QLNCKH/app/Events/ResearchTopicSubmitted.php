<?php

namespace App\Events;

use App\Models\DeTai; // Model đề tài của bạn
use App\Models\User;  // Model User
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Gửi ngay, không qua queue
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResearchTopicSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DeTai $deTai;
    public User $lecturer;

    /**
     * Create a new event instance.
     */
    public function __construct(DeTai $deTai, User $lecturer)
    {
        $this->deTai = $deTai;
        $this->lecturer = $lecturer;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Kênh riêng tư cho admin, chỉ admin mới nghe được
        return [
            new PrivateChannel('admin-notifications'),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        // Tên sự kiện sẽ được gửi đi
        return 'research.topic.submitted';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'topic_id' => $this->deTai->id, // Hoặc ma_de_tai nếu bạn dùng
            'topic_name' => $this->deTai->ten_de_tai,
            'lecturer_name' => $this->lecturer->ho_ten,
            'lecturer_msvc' => $this->lecturer->msvc,
            'message' => "Giảng viên {$this->lecturer->ho_ten} ({$this->lecturer->msvc}) vừa đăng ký đề tài mới: {$this->deTai->ten_de_tai}.",
            'submitted_at' => now()->toDateTimeString(),
        ];
    }
}
