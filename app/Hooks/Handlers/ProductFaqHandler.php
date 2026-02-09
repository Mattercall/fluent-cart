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
                .fct-product-faqs {
                    margin-top: 36px;
                    padding: 28px;
                    border: 1px solid #e4e7ec;
                    border-radius: 16px;
                    background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%);
                }

                .fct-faq-title {
                    margin: 0 0 20px;
                    font-size: 28px;
                    font-weight: 700;
                    line-height: 1.3;
                    color: #101828;
                }

                .fct-faq-list {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 14px;
                    counter-reset: faq-question;
                }

                .fct-faq-item {
                    border: 1px solid #eaecf0;
                    border-radius: 12px;
                    background: #ffffff;
                    padding: 16px 18px;
                    transition: border-color .2s ease, box-shadow .2s ease;
                }

                .fct-faq-item:hover,
                .fct-faq-item[open] {
                    border-color: #d0d5dd;
                    box-shadow: 0 6px 16px rgba(16, 24, 40, 0.08);
                }

                .fct-faq-question {
                    list-style: none;
                    cursor: pointer;
                    display: grid;
                    grid-template-columns: auto 1fr;
                    column-gap: 12px;
                    align-items: start;
                    font-size: 16px;
                    line-height: 1.5;
                    color: #101828;
                    font-weight: 600;
                }

                .fct-faq-question::-webkit-details-marker {
                    display: none;
                }

                .fct-faq-question::before {
                    counter-increment: faq-question;
                    content: counter(faq-question);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 30px;
                    height: 30px;
                    border-radius: 999px;
                    font-size: 14px;
                    font-weight: 700;
                    color: #1d2939;
                    background: #f2f4f7;
                }

                .fct-faq-answer {
                    color: #344054;
                    line-height: 1.7;
                    padding: 12px 0 4px 42px;
                    white-space: pre-line;
                }

                @media (min-width: 992px) {
                    .fct-faq-list {
                        grid-template-columns: repeat(2, minmax(0, 1fr));
                        gap: 16px;
                    }
                }

                @media (max-width: 640px) {
                    .fct-product-faqs {
                        padding: 20px;
                    }

                    .fct-faq-title {
                        font-size: 24px;
                    }
                }
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
