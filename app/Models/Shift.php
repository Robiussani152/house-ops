<?php

namespace App\Models;

use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'starts_at', 'ends_at'])]
class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    /**
     * @return HasMany<Staff, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
