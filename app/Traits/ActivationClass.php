<?php

namespace App\Traits;

/**
 * ActivationClass — license activation removed.
 * All methods are no-ops that always return true/pass.
 */
trait ActivationClass
{
    public function dmvf($request): string 
    {
        return 'step3';
    }

    public function actch(): bool
    {
        return true;
    }

    public function is_local(): bool
    {
        return in_array(request()->ip(), ['127.0.0.1', '::1']);
    }
}
