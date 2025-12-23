<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'ledger_account_type_id',
        'parent_id',
        'code',
        'name',
        'description',
        'level',
        'account_type',
        'acc_type',
        'entity_type',
        'self_added'
    ];
    public function children()
    {
        return $this->hasMany(StoreAccount::class, 'parent_id')
            ->where('store_id', Helpers::get_store_id())
            ->with('children'); // recursive eager load
    }

    // Define relationship to LedgerAccountType
    public function ledgerAccountType()
    {
        return $this->belongsTo(LedgerAccountType::class, 'ledger_account_type_id');
    }

    // Optional: relationship to parent account
    public function parent()
    {
        return $this->belongsTo(StoreAccount::class, 'parent_id');
    }
    public function allParents()
    {
        return $this->parent()->with('allParents');
    }
    // In StoreAccount model
    public function getFullHierarchyAttribute()
    {
        $parents = [];
        $current = $this->parent;

        while ($current) {
            $parents[] = $current->name; // or use $current->id if needed
            $current = $current->parent;
        }

        $parents = array_reverse($parents); // optional: root first
        $parents[] = $this->name; // include self at the end

        return implode('/', $parents);
    }
    public function ledgerEntries()
    {
        return $this->hasMany(StoreLedgerEntry::class, 'account_id');
    }
    public function getAllChildIds()
    {
        $ids = collect([$this->id]);

        foreach ($this->children as $child) {
            $ids = $ids->merge($child->getAllChildIds());
        }

        return $ids;
    }
}
