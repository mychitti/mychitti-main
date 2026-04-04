<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRecentSearch extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $table = 'user_recent_searches'; // Explicitly define the table name
    protected $fillable = ['text', 'url', 'user_id', 'type', 'type_id', 'zone_ids']; // Specify the fields that are mass assignable
}
