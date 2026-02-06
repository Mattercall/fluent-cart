<?php

namespace FluentCart\App\Hooks\Handlers;

use FluentCart\App\Models\ProductFaq;

class ProductFaqHandler
{
    public function register()
    {
        add_action('fluent_cart/product/after_product_content', [$this, 'renderSingleProductFaqs'], 20, 1);
    }

    public function renderSingleProductFaqs($productId)
    {
        $faqs = ProductFaq::query()->orderBy('ID', 'DESC')->get()->map(function ($faq) {
            return [
                'id' => absint($faq->ID),
                'question' => get_post_meta($faq->ID, '_fc_faq_question', true) ?: $faq->post_title,
                'answer' => get_post_meta($faq->ID, '_fc_faq_answer', true) ?: $faq->post_content,
                'enabled' => get_post_meta($faq->ID, '_fc_faq_enabled', true) ?: 'yes',
                'sort_order' => intval(get_post_meta($faq->ID, '_fc_faq_sort_order', true)),
                'product_id' => absint(get_post_meta($faq->ID, '_fc_faq_product_id', true))
            ];
        })->filter(function ($faq) use ($productId) {
            return $faq['enabled'] === 'yes' && absint($faq['product_id']) === absint($productId);
        })->sortBy('sort_order')->values();

        if (!$faqs->count()) {
            return;
        }
        ?>
        <section class="fct-product-faqs" aria-label="<?php echo esc_attr__('Product FAQs', 'fluent-cart'); ?>">
            <style>
                .fct-product-faqs{margin-top:28px;padding:24px;border:1px solid #e6e8ee;border-radius:12px;background:#fff}.fct-faq-title{font-size:24px;font-weight:700;margin-bottom:16px}.fct-faq-list{display:grid;gap:10px}.fct-faq-item{border:1px solid #edf0f5;border-radius:10px;background:#fafbff;padding:12px 14px}.fct-faq-question{font-weight:600;cursor:pointer}.fct-faq-answer{color:#344054;line-height:1.5;padding-top:8px;white-space:pre-line}
            </style>
            <h3 class="fct-faq-title"><?php echo esc_html__('Frequently asked questions', 'fluent-cart'); ?></h3>
            <div class="fct-faq-list">
                <?php foreach ($faqs as $faq): ?>
                    <details class="fct-faq-item">
                        <summary class="fct-faq-question"><?php echo esc_html($faq['question']); ?></summary>
                        <div class="fct-faq-answer"><?php echo esc_html($faq['answer']); ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
