<?php

namespace FluentCart\App\Services;

class ProductSocialProofService
{
    const META_KEY = '_fct_product_added_to_cart_count';

    public static function getCount($productId)
    {
        $productId = (int)$productId;

        if ($productId <= 0) {
            return 0;
        }

        $count = (int)get_post_meta($productId, self::META_KEY, true);

        return max($count, 0);
    }

    public static function increment($productId, $step = 1)
    {
        $productId = (int)$productId;
        $step = max(1, (int)$step);

        if ($productId <= 0) {
            return 0;
        }

        $count = self::getCount($productId) + $step;
        update_post_meta($productId, self::META_KEY, $count);

        return $count;
    }
}
