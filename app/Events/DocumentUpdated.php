<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $documentId,
        public string $title,
        public string $content,
        public string $updatedBy
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('document.' . $this->documentId);
    }

    public function broadcastAs(): string
    {
        return 'document.updated';
    }
}