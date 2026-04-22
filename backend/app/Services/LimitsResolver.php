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

                // Numeric: take highest max (-1 = unlimited always wins)
                if (isset($value['max'], $merged[$key]['max'])) {
                    if ($value['max'] === -1 || $merged[$key]['max'] === -1) {
                        $merged[$key]['max'] = -1;
                    } else {
                        $merged[$key]['max'] = max($merged[$key]['max'], $value['max']);
                    }
                }

                // Boolean: if any plan enables the feature, result is true
                if (isset($value['value'], $merged[$key]['value']) && is_bool($value['value'])) {
                    $merged[$key]['value'] = $merged[$key]['value'] || $value['value'];
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
