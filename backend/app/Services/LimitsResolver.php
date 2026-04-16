<?php

namespace App\Services;

use App\Models\Subscription;

class LimitsResolver
{
    /**
     * Devuelve los límites efectivos: plan base mergeado con el override de la suscripción.
     * El override solo sobreescribe lo que especifica; el 'type' siempre viene del plan.
     *
     * Ejemplo:
     *   plan:     { "members": { "type": "permanent", "max": 25 } }
     *   override: { "members": { "max": 100 } }
     *   efectivo: { "members": { "type": "permanent", "max": 100 } }
     */
    public function resolve(Subscription $subscription): array
    {
        $base     = $subscription->plan->limits_json ?? [];
        $override = $subscription->override_json ?? [];

        $result = $base;

        foreach ($override as $key => $values) {
            if (isset($result[$key]) && is_array($values)) {
                $result[$key] = array_merge($result[$key], $values);
            }
        }

        return $result;
    }
}
