<?php

namespace FluentCart\App\Hooks\Handlers;

use FluentCart\App\Modules\Data\ProductDataSetup;
use FluentCart\App\Services\Renderer\ProductRenderer;

class ProductPromotionalSectionHandler
{
    public function register()
    {
        add_action('fluent_cart/product/after_product_content', [$this, 'renderSingleProductPromotionalSections'], 5, 1);
    }

    public function renderSingleProductPromotionalSections($productId)
    {
        $product = $GLOBALS['fct_product'] ?? ProductDataSetup::getProductModel($productId);

        if (!$product || !$product->detail) {
            return;
        }

        (new ProductRenderer($product))->renderPromotionalSection();
    }
}
