<?php

namespace App\Traits;

use App\Services\HashId;

trait HasHashId
{
    public function initializeHasHashId(): void
    {
        $this->makeHidden('id');
    }

    public function getHashId(): string
    {
        return HashId::encode((int) $this->getKey());
    }

    public function getRouteKey(): string
    {
        return $this->getHashId();
    }

    public function resolveRouteBinding(mixed $value, $field = null): static|null
    {
        $id = HashId::decode((string) $value);

        if ($id === null) {
            return null;
        }

        return $this->where($this->getKeyName(), $id)->first();
    }
}
