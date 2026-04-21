<?php

namespace App\Values;

class ResolvedLimits
{
    private function __construct(private readonly array $raw) {}

    public static function from(array $raw): self
    {
        return new self($raw);
    }

    public function maxWorkspaces(): int        { return $this->raw['workspaces']['max'] ?? -1; }
    public function maxDepth(): int             { return $this->raw['workspace_depth']['max'] ?? -1; }
    public function maxStorageGb(): int         { return $this->raw['storage_gb']['max'] ?? -1; }
    public function maxMembers(): int           { return $this->raw['members']['max'] ?? -1; }
    public function maxStreamsConcurrent(): int { return $this->raw['streams_concurrent']['max'] ?? -1; }
    public function maxStreamMinutes(): int     { return $this->raw['stream_minutes']['max'] ?? -1; }
    public function maxRoomsConcurrent(): int   { return $this->raw['rooms_concurrent']['max'] ?? -1; }
    public function maxRoomParticipants(): int  { return $this->raw['room_participants']['max'] ?? -1; }
    public function maxRoomGuests(): int        { return $this->raw['room_guests']['max'] ?? -1; }

    public function toArray(): array
    {
        return $this->raw;
    }
}
