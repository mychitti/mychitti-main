<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

 
class VendorModuleInstruction extends Model
{
    use HasFactory;
      protected $fillable = [
        'name',
        'slug',
    ];


  protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = $model->generateSlug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name')) {
                $model->slug = $model->generateSlug($model->name);
            }
        });
    }

    private function generateSlug(string $name): string
    {
        $slug = Str::slug($name);

        $allSlugs = static::withoutGlobalScopes()->where('slug', 'like', $slug . '%')->pluck('slug')->toArray();

        if (! in_array($slug, $allSlugs, true)) {
            return $slug;
        }

        $max = 0;
        foreach ($allSlugs as $s) {
            if (preg_match('/^' . preg_quote($slug, '/') . '-([0-9]+)$/', $s, $m)) {
                $n = (int) $m[1];
                if ($n > $max) $max = $n;
            }
        }

        return $slug . '-' . ($max + 1);
    }
}
