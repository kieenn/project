<?php

// Example: app/Events/BaiBaoSubmitted.php
namespace App\Events;

use App\Models\BaiBao;
use App\Models\DeTai;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Changed from ShouldBroadcast
use Illuminate\Broadcasting\InteractsWithSockets; // Keep this
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BaiBaoSubmitted implements ShouldBroadcastNow // Changed from ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $baiBao;
    public $lecturer;
    public $deTai;

    public function __construct(BaiBao $baiBao, User $lecturer, DeTai $deTai)
    {
        $this->baiBao = $baiBao;
        $this->lecturer = $lecturer;
        $this->deTai = $deTai;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('admin-notifications'); // Channel for admins
    }

    public function broadcastAs()
    {
        return 'bai-bao.submitted';
    }

    public function broadcastWith()
    {
        return [
            'bai_bao_id' => $this->baiBao->id,
            'ten_bai_bao' => $this->baiBao->ten_bai_bao,
            'de_tai_ten' => $this->deTai->ten_de_tai,
            'lecturer_name' => $this->lecturer->ho_ten,
            'message' => "Bài báo mới '{$this->baiBao->ten_bai_bao}' cho đề tài '{$this->deTai->ten_de_tai}' đã được nộp bởi {$this->lecturer->ho_ten}."
        ];
    }
}
