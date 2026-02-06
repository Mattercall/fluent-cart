<?php

namespace FluentCart\App\Hooks\Handlers;

use FluentCart\App\Models\CustomerReview;

class CustomerReviewHandler
{
    public function register()
    {
        add_action('fluent_cart/product/after_product_content', [$this, 'renderSingleProductReviews'], 15, 1);
    }

    public function renderSingleProductReviews($productId)
    {
        $reviews = CustomerReview::query()->orderBy('ID', 'DESC')->get()->map(function ($review) {
            return [
                'id' => absint($review->ID),
                'reviewer_name' => get_post_meta($review->ID, '_fc_review_reviewer_name', true) ?: $review->post_title,
                'country' => get_post_meta($review->ID, '_fc_review_country', true),
                'country_flag' => get_post_meta($review->ID, '_fc_review_country_flag', true),
                'rating' => absint(get_post_meta($review->ID, '_fc_review_rating', true)),
                'review_text' => get_post_meta($review->ID, '_fc_review_review_text', true) ?: $review->post_content,
                'review_time' => get_post_meta($review->ID, '_fc_review_review_time', true) ?: $review->post_date,
                'enabled' => get_post_meta($review->ID, '_fc_review_enabled', true) ?: 'yes',
                'sort_order' => intval(get_post_meta($review->ID, '_fc_review_sort_order', true)),
                'product_id' => absint(get_post_meta($review->ID, '_fc_review_product_id', true))
            ];
        })->filter(function ($review) use ($productId) {
            return $review['enabled'] === 'yes' && absint($review['product_id']) === absint($productId);
        })->sortBy([
            ['sort_order', 'asc'],
            ['review_time', 'desc']
        ])->values();

        if (!$reviews->count()) {
            return;
        }

        $avgRating = round($reviews->avg('rating'), 1);
        ?>
        <section class="fct-customer-reviews" aria-label="<?php echo esc_attr__('Customer reviews', 'fluent-cart'); ?>">
            <style>
                .fct-customer-reviews{margin-top:28px;padding:24px;border:1px solid #e6e8ee;border-radius:12px;background:#fff}.fct-cr-header{display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;margin-bottom:16px}.fct-cr-summary{font-size:24px;font-weight:700}.fct-cr-count{color:#667085}.fct-cr-list{display:grid;grid-template-columns:1fr;gap:14px}.fct-cr-item{padding:14px;border:1px solid #edf0f5;border-radius:10px;background:#fafbff}.fct-cr-meta{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:8px}.fct-cr-name{font-weight:600}.fct-cr-country{color:#667085;display:flex;gap:6px;align-items:center;line-height:1.4}.fct-cr-country-flag{display:inline-flex;align-items:center;justify-content:center;min-width:1.2em;font-size:1rem}.fct-cr-stars{color:#f59e0b;letter-spacing:2px}.fct-cr-time{color:#98a2b3;font-size:13px}@media (max-width: 768px){.fct-customer-reviews{padding:16px}.fct-cr-summary{font-size:20px}}
            </style>
            <div class="fct-cr-header">
                <div>
                    <div class="fct-cr-summary"><?php echo esc_html($avgRating); ?>/5 ★</div>
                    <div class="fct-cr-count"><?php echo esc_html(sprintf(__('%d reviews', 'fluent-cart'), $reviews->count())); ?></div>
                </div>
            </div>
            <div class="fct-cr-list">
                <?php foreach ($reviews as $review): ?>
                    <?php $countryFlag = $this->resolveCountryFlag($review['country_flag'], $review['country']); ?>
                    <article class="fct-cr-item">
                        <div class="fct-cr-meta">
                            <div>
                                <div class="fct-cr-name"><?php echo esc_html($review['reviewer_name']); ?></div>
                                <div class="fct-cr-country">
                                    <?php if ($countryFlag): ?>
                                        <span class="fct-cr-country-flag" aria-hidden="true"><?php echo esc_html($countryFlag); ?></span>
                                    <?php endif; ?>
                                    <span><?php echo esc_html($review['country']); ?></span>
                                </div>
                            </div>
                            <div>
                                <div class="fct-cr-stars"><?php echo esc_html(str_repeat('★', max(1, min(5, $review['rating'])))); ?></div>
                                <div class="fct-cr-time"><?php echo esc_html($this->formatReviewDate($review['review_time'])); ?></div>
                            </div>
                        </div>
                        <div><?php echo esc_html($review['review_text']); ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    private function resolveCountryFlag($savedFlag, $country)
    {
        $savedFlag = trim((string)$savedFlag);
        $countryCode = strtoupper(trim((string)$country));
        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return $savedFlag;
        }

        $regionalA = 0x1F1E6;
        $offset = ord('A');

        return mb_chr($regionalA + (ord($countryCode[0]) - $offset), 'UTF-8')
            . mb_chr($regionalA + (ord($countryCode[1]) - $offset), 'UTF-8');
    }

    private function formatReviewDate($reviewTime)
    {
        $reviewTime = trim((string)$reviewTime);
        if ($reviewTime === '') {
            return '';
        }

        $timezone = wp_timezone();
        $timestamp = false;

        try {
            $utcDate = new \DateTimeImmutable($reviewTime, new \DateTimeZone('UTC'));
            $timestamp = $utcDate->setTimezone($timezone)->getTimestamp();
        } catch (\Exception $e) {
            $timestamp = strtotime($reviewTime);
        }

        if (!$timestamp) {
            return $reviewTime;
        }

        $dateTimeFormat = trim(get_option('date_format') . ' ' . get_option('time_format'));

        return wp_date($dateTimeFormat, $timestamp, $timezone);
    }
}
