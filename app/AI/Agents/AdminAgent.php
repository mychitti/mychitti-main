<?php 

namespace App\AI\Agents;

use Illuminate\Support\Facades\DB;
 
class AdminAgent
{
    public function systemPrompt(): string
    {
        return DB::table('system_prompts')
            ->where('user_type', 'admin')
            ->value('prompt') ?? '';
    } 
}
