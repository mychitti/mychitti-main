<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MonthlyMaintanance extends Model
{
    protected $table = 'monthly_maintanance';

    protected $fillable = [
        'store_id', 'expense_type','payment_day' ,'parent', 'for_month', 'title','due','master', 'amount', 'month', 'paid_on', 'notes', 'expense_type'
    ];
}
