<?php

namespace App\Events;

use App\Models\DeTai;
use App\Models\User; // Admin who rejected
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeTaiRejected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DeTai $deTai;
    public User $admin;
    public string $reason;

    public function __construct(DeTai $deTai, User $admin, string $reason)
    {
        $this->deTai = $deTai;
        $this->admin = $admin;
        $this->reason = $reason;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('lecturer-notifications.' . $this->deTai->msvc_gvdk),
        ];
    }

    public function broadcastAs()
    {
        return 'detai.rejected';
    }

    public function broadcastWith(): array
    {
        return [
            'de_tai_id' => $this->deTai->id,
            'ten_de_tai' => $this->deTai->ten_de_tai,
            'admin_name' => $this->admin->ho_ten,
            'reason' => $this->reason,
            'message' => "Đề tài '{$this->deTai->ten_de_tai}' của bạn đã bị từ chối bởi admin {$this->admin->ho_ten}. Lý do: {$this->reason}",
            'rejected_at' => now()->toDateTimeString(),
        ];
    }
}
