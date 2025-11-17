<?php

namespace Jiny\Subscribe\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\subscribeFactory;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return subscribeFactory::new();
    }

    protected $fillable = [
        'enable',
        'featured',
        'view_count',
        'slug',
        'title',
        'description',
        'content',
        'category',
        'category_id',
        'price',
        'sale_price',
        'duration',
        'image',
        'images',
        'features',
        'process',
        'requirements',
        'deliverables',
        'tags',
        'meta_title',
        'meta_description',
        'manager',
    ];

    protected $casts = [
        'enable' => 'boolean',
        'featured' => 'boolean',
        'view_count' => 'integer',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'images' => 'array',
        'features' => 'array',
        'process' => 'array',
        'requirements' => 'array',
        'deliverables' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(subscribeCategory::class, 'category_id');
    }

    public function plans()
    {
        return $this->hasMany(subscribePlan::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
