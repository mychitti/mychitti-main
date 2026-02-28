<?php

namespace App\AI;

use App\AI\Agents\UserAgent;
use App\AI\Agents\VendorAgent;
use App\AI\Agents\AdminAgent;

class AgentFactory
{
    public static function make(string $type)
    { 
        return match ($type) {
            'vendor' => new VendorAgent(),
            'admin'  => new AdminAgent(),
            default  => new UserAgent(),
        };
    }
}
 