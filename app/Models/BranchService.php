<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchService extends Pivot
{
    protected $table = 'branch_item';
    protected $fillable = [
        'store_id',
        'branch_id',
        'item_id',
        'price',
    ];
}
