<?php

namespace App\Events;

use App\Models\DeTai;
use App\Models\User; // Admin who approved
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeTaiApproved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DeTai $deTai;
    public User $admin;

    public function __construct(DeTai $deTai, User $admin)
    {
        $this->deTai = $deTai;
        $this->admin = $admin;
    }

    public function broadcastOn(): array
    {
        // Gửi đến kênh riêng của người đăng ký đề tài (msvc_gvdk)
        return [
            new PrivateChannel('lecturer-notifications.' . $this->deTai->msvc_gvdk),
        ];
    }

    public function broadcastAs()
    {
        return 'detai.approved';
    }

    public function broadcastWith(): array
    {
        return [
            'de_tai_id' => $this->deTai->id,
            'ma_de_tai' => $this->deTai->ma_de_tai,
            'ten_de_tai' => $this->deTai->ten_de_tai,
            'admin_name' => $this->admin->ho_ten,
            'message' => "Đề tài '{$this->deTai->ten_de_tai}' của bạn đã được duyệt bởi admin {$this->admin->ho_ten}.",
            'approved_at' => now()->toDateTimeString(),
        ];
    }
}
