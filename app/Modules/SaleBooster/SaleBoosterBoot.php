<?php

namespace FluentCart\App\Modules\SaleBooster;

use FluentCart\Api\ModuleSettings;
use FluentCart\Framework\Support\Arr;

class SaleBoosterBoot
{
    public function register()
    {
        add_action('fluent_cart/product/single/after_gallery', [$this, 'renderContent'], 10, 1);
    }

    public function renderContent($payload = [])
    {
        $product = Arr::get($payload, 'product');

        if (!$product || empty($product->ID)) {
            return;
        }

        $settings = ModuleSettings::getSettings('sale_booster');
        $rows = Arr::get($settings, 'products', []);

        if (empty($rows) || !is_array($rows)) {
            return;
        }

        $productRow = null;
        foreach ($rows as $row) {
            if ((int) Arr::get($row, 'product_id') === (int) $product->ID) {
                $productRow = $row;
                break;
            }
        }

        if (!$productRow) {
            return;
        }

        $videoUrl = esc_url(Arr::get($productRow, 'video_url', ''));
        $images = Arr::get($productRow, 'images', []);

        if (!$videoUrl && empty($images)) {
            return;
        }
        ?>
        <div class="fct-sale-booster" data-fct-sale-booster>
            <?php if ($videoUrl): ?>
                <div class="fct-sale-booster-video-wrap">
                    <a class="fct-sale-booster-video-button" href="<?php echo esc_url($videoUrl); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html__('Watch Product Video', 'fluent-cart'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!empty($images) && is_array($images)): ?>
                <div class="fct-sale-booster-images">
                    <?php foreach ($images as $image):
                        $imageUrl = esc_url(Arr::get($image, 'url', ''));
                        $description = sanitize_text_field(Arr::get($image, 'description', ''));

                        if (!$imageUrl) {
                            continue;
                        }
                        ?>
                        <figure class="fct-sale-booster-image-item">
                            <img src="<?php echo esc_url($imageUrl); ?>" alt="<?php echo esc_attr($description ?: __('Sale booster image', 'fluent-cart')); ?>" />
                            <?php if ($description): ?>
                                <figcaption><?php echo esc_html($description); ?></figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <style>
            .fct-sale-booster { margin-top: 16px; display: flex; flex-direction: column; gap: 14px; }
            .fct-sale-booster-video-button { display: inline-flex; align-items: center; justify-content: center; background: #111827; color: #fff; padding: 10px 14px; border-radius: 8px; text-decoration: none; font-weight: 600; }
            .fct-sale-booster-images { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; }
            .fct-sale-booster-image-item { margin: 0; }
            .fct-sale-booster-image-item img { width: 100%; height: auto; border-radius: 8px; display: block; }
            .fct-sale-booster-image-item figcaption { margin-top: 6px; font-size: 12px; color: #4b5563; }
        </style>
        <?php
    }
}
