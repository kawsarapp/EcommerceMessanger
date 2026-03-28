<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    // A helper method on the item itself
    public function getResolvedUrlAttribute()
    {
        if ($this->type === 'custom_link') {
            return $this->url;
        }

        if ($this->type === 'category') {
            $cat = Category::find($this->reference_id);
            if ($cat) {
                // Return query string format for category, wait, we don't know $baseUrl here seamlessly,
                // but we can return just the query string part. The best way is to let the blade handle it.
                return '?category=' . $cat->slug;
            }
        }

        if ($this->type === 'page') {
            $page = Page::find($this->reference_id);
            if ($page) {
                return 'page/' . $page->slug;
            }
        }

        return '#';
    }
}
