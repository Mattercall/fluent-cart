<?php

namespace FluentCart\App\Hooks\Handlers;

use FluentCart\App\Http\Controllers\ProductFaqController;
use FluentCart\Framework\Support\Arr;
use FluentCart\Framework\Support\Helper;

class ProductFaqHandler
{
    public function register()
    {
        add_action('fluent_cart/product/after_product_content', [$this, 'renderSingleProductFaqs'], 20, 1);
    }

    public function renderSingleProductFaqs($productId)
    {
        $faqs = Helper::collect(get_option(ProductFaqController::OPTION_KEY, []))->map(function ($faq) {
            return [
                'id' => absint(Arr::get($faq, 'id')),
                'question' => sanitize_text_field(Arr::get($faq, 'question', '')),
                'answer' => wp_kses_post(Arr::get($faq, 'answer', '')),
                'product_id' => absint(Arr::get($faq, 'product_id')),
                'sort_order' => intval(Arr::get($faq, 'sort_order', 0)),
                'enabled' => Arr::get($faq, 'enabled') === 'no' ? 'no' : 'yes',
                'is_global' => Arr::get($faq, 'is_global') === 'yes' ? 'yes' : 'no'
            ];
        })->filter(function ($faq) use ($productId) {
            if (empty($faq['question']) || empty(wp_strip_all_tags($faq['answer'])) || $faq['enabled'] !== 'yes') {
                return false;
            }

            return $faq['is_global'] === 'yes' || absint($faq['product_id']) === absint($productId);
        })->sortBy([
            ['sort_order', 'asc'],
            ['id', 'asc']
        ])->values();

        if (!$faqs->count()) {
            return;
        }

        ?>
        <section class="fct-product-faqs" aria-label="<?php echo esc_attr__('Product FAQs', 'fluent-cart'); ?>">
            <style>
                .fct-product-faqs{margin-top:28px;padding:24px;border:1px solid #e6e8ee;border-radius:12px;background:#fff}.fct-product-faqs-title{font-size:24px;font-weight:700;margin:0 0 14px}.fct-product-faqs-list{display:grid;gap:12px}.fct-faq-item{border:1px solid #edf0f5;border-radius:10px;overflow:hidden;background:#fafbff}.fct-faq-item summary{list-style:none;cursor:pointer;padding:16px 18px;font-weight:600;position:relative;padding-right:48px}.fct-faq-item summary::-webkit-details-marker{display:none}.fct-faq-item summary:after{content:'+';position:absolute;right:18px;top:50%;transform:translateY(-50%);font-size:24px;line-height:1;color:#344054}.fct-faq-item[open] summary:after{content:'–'}.fct-faq-answer{padding:0 18px 16px;color:#475467;line-height:1.7}.fct-faq-answer p:last-child{margin-bottom:0}@media (max-width: 768px){.fct-product-faqs{padding:16px}.fct-product-faqs-title{font-size:20px}}
            </style>
            <h3 class="fct-product-faqs-title"><?php echo esc_html__('Frequently Asked Questions', 'fluent-cart'); ?></h3>
            <div class="fct-product-faqs-list">
                <?php foreach ($faqs as $faq): ?>
                    <details class="fct-faq-item">
                        <summary><?php echo esc_html($faq['question']); ?></summary>
                        <div class="fct-faq-answer"><?php echo wp_kses_post($faq['answer']); ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
