<?php

namespace FluentCart\App\Models;

use FluentCart\App\CPT\ProductFaq as ProductFaqCPT;
use FluentCart\Framework\Database\Orm\Builder;

class ProductFaq extends Model
{
    protected $table = 'posts';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'post_title',
        'post_content',
        'post_status',
        'post_type',
        'post_date',
        'post_date_gmt',
        'post_modified',
        'post_modified_gmt'
    ];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('post_type', function (Builder $builder) {
            $builder->where('post_type', '=', ProductFaqCPT::CPT_NAME);
        });
    }
}
