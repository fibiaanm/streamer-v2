<?php

namespace App\Services;

use App\Models\EnterpriseProduct;
use App\Values\ResolvedLimits;
use Illuminate\Support\Collection;

class LimitsResolver
{
    public function resolve(Collection $enterpriseProducts): ResolvedLimits
    {
        $merged = [];

        foreach ($enterpriseProducts as $ep) {
            foreach ($this->resolveOne($ep) as $key => $value) {
                if (!isset($merged[$key])) {
                    $merged[$key] = $value;
                    continue;
                }

                // For overlapping keys take the highest max (-1 = unlimited always wins)
                if (isset($value['max'], $merged[$key]['max'])) {
                    if ($value['max'] === -1 || $merged[$key]['max'] === -1) {
                        $merged[$key]['max'] = -1;
                    } else {
                        $merged[$key]['max'] = max($merged[$key]['max'], $value['max']);
                    }
                }
            }
        }

        return ResolvedLimits::from($merged);
    }

    public function byProduct(Collection $enterpriseProducts): array
    {
        return $enterpriseProducts
            ->mapWithKeys(fn (EnterpriseProduct $ep) => [
                $ep->product->slug => [
                    'plan'   => $ep->plan->name,
                    'limits' => $this->resolveOne($ep),
                ],
            ])
            ->all();
    }

    private function resolveOne(EnterpriseProduct $ep): array
    {
        $base     = $ep->plan->limits_json ?? [];
        $override = $ep->override_json ?? [];

        $result = $base;

        foreach ($override as $key => $values) {
            if (isset($result[$key]) && is_array($values)) {
                $result[$key] = array_merge($result[$key], $values);
            }
        }

        return $result;
    }
}
