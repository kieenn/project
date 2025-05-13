<?php

namespace App\Events;

use App\Models\BaiBao;
use App\Models\User; // Admin who approved
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BaiBaoApproved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BaiBao $baiBao;
    public User $admin;

    public function __construct(BaiBao $baiBao, User $admin)
    {
        $this->baiBao = $baiBao;
        $this->admin = $admin;
    }

    public function broadcastOn(): array
    {
        // Gửi đến kênh riêng của người nộp bài báo (msvc_nguoi_nop)
        return [
            new PrivateChannel('lecturer-notifications.' . $this->baiBao->msvc_nguoi_nop),
        ];
    }

    public function broadcastAs()
    {
        return 'baibao.approved';
    }

    public function broadcastWith(): array
    {
        return [
            'bai_bao_id' => $this->baiBao->id,
            'ten_bai_bao' => $this->baiBao->ten_bai_bao,
            'de_tai_ten' => $this->baiBao->deTai->ten_de_tai ?? 'N/A', // Lấy tên đề tài nếu có
            'admin_name' => $this->admin->ho_ten,
            'message' => "Bài báo '{$this->baiBao->ten_bai_bao}' của bạn đã được duyệt bởi admin {$this->admin->ho_ten}.",
            'approved_at' => now()->toDateTimeString(),
        ];
    }
}
