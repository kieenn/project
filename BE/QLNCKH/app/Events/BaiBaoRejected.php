<?php

namespace App\Events;

use App\Models\BaiBao;
use App\Models\User; // Admin who rejected
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BaiBaoRejected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BaiBao $baiBao;
    public User $admin;
    public string $reason;

    public function __construct(BaiBao $baiBao, User $admin, string $reason)
    {
        $this->baiBao = $baiBao;
        $this->admin = $admin;
        $this->reason = $reason;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('lecturer-notifications.' . $this->baiBao->msvc_nguoi_nop),
        ];
    }

    public function broadcastAs()
    {
        return 'baibao.rejected';
    }

    public function broadcastWith(): array
    {
        return [
            'bai_bao_id' => $this->baiBao->id,
            'ten_bai_bao' => $this->baiBao->ten_bai_bao,
            'de_tai_ten' => $this->baiBao->deTai->ten_de_tai ?? 'N/A',
            'admin_name' => $this->admin->ho_ten,
            'reason' => $this->reason,
            'message' => "Bài báo '{$this->baiBao->ten_bai_bao}' của bạn đã bị từ chối bởi admin {$this->admin->ho_ten}. Lý do: {$this->reason}",
            'rejected_at' => now()->toDateTimeString(),
        ];
    }
}
