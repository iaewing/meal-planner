<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class RecipeImage extends Model
{
    protected $fillable = [
        'recipe_id',
        'path',
        'disk',
        'sort_order',
    ];

    protected $appends = ['image_url'];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        $disk = Storage::disk($this->disk);

        if ($this->disk === 'public') {
            return $disk->url($this->path);
        }

        return $disk->temporaryUrl($this->path, Carbon::now()->addMinutes(90));
    }
}
