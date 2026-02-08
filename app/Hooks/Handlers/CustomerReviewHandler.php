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
                .fct-customer-reviews{margin-top:28px;padding:28px;border:1px solid #e7eaf0;border-radius:16px;background:linear-gradient(180deg,#fff 0%,#fbfcff 100%);box-shadow:0 12px 28px rgba(15,23,42,.06)}.fct-cr-header{display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;margin-bottom:20px}.fct-cr-summary{font-size:24px;font-weight:700;color:#111827}.fct-cr-count{color:#667085}.fct-cr-slider{position:relative;overflow:hidden}.fct-cr-track{display:flex;align-items:stretch;transition:transform .45s ease}.fct-cr-slide{width:100%;min-width:100%;box-sizing:border-box}.fct-cr-item{height:100%;padding:18px;border:1px solid #e9ecf4;border-radius:14px;background:#fff;box-shadow:0 4px 16px rgba(15,23,42,.05)}.fct-cr-meta{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #eef1f6}.fct-cr-name{font-weight:700;color:#101828}.fct-cr-country{color:#667085;font-size:14px}.fct-cr-stars{color:#f59e0b;letter-spacing:2px}.fct-cr-time{color:#98a2b3;font-size:13px;margin-top:4px}.fct-cr-text{color:#344054;line-height:1.65}.fct-cr-controls{display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-top:14px}.fct-cr-arrow{height:34px;width:34px;border-radius:999px;border:1px solid #e4e7ec;background:#fff;color:#344054;cursor:pointer;line-height:1;display:inline-flex;align-items:center;justify-content:center}.fct-cr-arrow:hover{background:#f8fafc}.fct-cr-dots{display:flex;gap:6px}.fct-cr-dot{height:7px;width:7px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0}.fct-cr-dot.is-active{width:22px;background:#111827}.fct-cr-slider.is-static .fct-cr-controls{display:none}@media (max-width: 768px){.fct-customer-reviews{padding:16px}.fct-cr-summary{font-size:20px}.fct-cr-item{padding:14px}}
            </style>
            <div class="fct-cr-header">
                <div>
                    <div class="fct-cr-summary"><?php echo esc_html($avgRating); ?>/5 ★</div>
                    <div class="fct-cr-count"><?php echo esc_html(sprintf(__('%d reviews', 'fluent-cart'), $reviews->count())); ?></div>
                </div>
            </div>
            <div class="fct-cr-slider" data-fct-review-slider>
                <div class="fct-cr-track" data-fct-review-slider-track>
                    <?php foreach ($reviews as $review): ?>
                        <div class="fct-cr-slide">
                            <article class="fct-cr-item">
                                <div class="fct-cr-meta">
                                    <div>
                                        <div class="fct-cr-name"><?php echo esc_html($review['reviewer_name']); ?></div>
                                        <div class="fct-cr-country"><?php echo wp_kses_post(trim($review['country_flag'] . ' ' . $review['country'])); ?></div>
                                    </div>
                                    <div>
                                        <div class="fct-cr-stars"><?php echo esc_html(str_repeat('★', max(1, min(5, $review['rating'])))); ?></div>
                                        <div class="fct-cr-time"><?php echo esc_html(human_time_diff(strtotime($review['review_time']), current_time('timestamp', true)) . ' ' . __('ago', 'fluent-cart')); ?></div>
                                    </div>
                                </div>
                                <div class="fct-cr-text"><?php echo esc_html($review['review_text']); ?></div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="fct-cr-controls">
                    <button type="button" class="fct-cr-arrow" data-fct-review-slider-prev aria-label="<?php esc_attr_e('Previous review', 'fluent-cart'); ?>">‹</button>
                    <div class="fct-cr-dots" data-fct-review-slider-dots></div>
                    <button type="button" class="fct-cr-arrow" data-fct-review-slider-next aria-label="<?php esc_attr_e('Next review', 'fluent-cart'); ?>">›</button>
                </div>
            </div>
            <script>
                (function () {
                    var slider = document.currentScript.previousElementSibling;
                    if (!slider) {
                        return;
                    }

                    var track = slider.querySelector('[data-fct-review-slider-track]');
                    var slides = slider.querySelectorAll('.fct-cr-slide');
                    var dotsWrap = slider.querySelector('[data-fct-review-slider-dots]');
                    var prevBtn = slider.querySelector('[data-fct-review-slider-prev]');
                    var nextBtn = slider.querySelector('[data-fct-review-slider-next]');
                    var totalSlides = slides.length;
                    var currentIndex = 0;
                    var autoSlideTimer;

                    if (!track || !totalSlides) {
                        return;
                    }

                    if (totalSlides === 1) {
                        slider.classList.add('is-static');
                    }

                    var setSliderHeight = function () {
                        var maxHeight = 0;
                        slides.forEach(function (slide) {
                            slide.style.minHeight = '0px';
                            maxHeight = Math.max(maxHeight, slide.offsetHeight);
                        });

                        slides.forEach(function (slide) {
                            slide.style.minHeight = maxHeight + 'px';
                        });

                        slider.style.minHeight = maxHeight + 56 + 'px';
                    };

                    var renderDots = function () {
                        if (!dotsWrap) {
                            return;
                        }

                        var dots = '';
                        for (var i = 0; i < totalSlides; i++) {
                            dots += '<button type="button" class="fct-cr-dot' + (i === 0 ? ' is-active' : '') + '" data-slide-index="' + i + '" aria-label="Review ' + (i + 1) + '"></button>';
                        }

                        dotsWrap.innerHTML = dots;
                    };

                    var updateSlider = function () {
                        track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';
                        if (!dotsWrap) {
                            return;
                        }

                        dotsWrap.querySelectorAll('.fct-cr-dot').forEach(function (dot, index) {
                            dot.classList.toggle('is-active', index === currentIndex);
                        });
                    };

                    var goToSlide = function (index) {
                        currentIndex = (index + totalSlides) % totalSlides;
                        updateSlider();
                    };

                    var restartAutoSlide = function () {
                        if (autoSlideTimer) {
                            clearInterval(autoSlideTimer);
                        }

                        if (totalSlides > 1) {
                            autoSlideTimer = setInterval(function () {
                                goToSlide(currentIndex + 1);
                            }, 5000);
                        }
                    };

                    renderDots();
                    setSliderHeight();
                    updateSlider();
                    restartAutoSlide();

                    if (prevBtn) {
                        prevBtn.addEventListener('click', function () {
                            goToSlide(currentIndex - 1);
                            restartAutoSlide();
                        });
                    }

                    if (nextBtn) {
                        nextBtn.addEventListener('click', function () {
                            goToSlide(currentIndex + 1);
                            restartAutoSlide();
                        });
                    }

                    if (dotsWrap) {
                        dotsWrap.addEventListener('click', function (event) {
                            var dot = event.target.closest('[data-slide-index]');
                            if (!dot) {
                                return;
                            }

                            goToSlide(parseInt(dot.getAttribute('data-slide-index'), 10));
                            restartAutoSlide();
                        });
                    }

                    slider.addEventListener('mouseenter', function () {
                        if (autoSlideTimer) {
                            clearInterval(autoSlideTimer);
                        }
                    });

                    slider.addEventListener('mouseleave', restartAutoSlide);
                    window.addEventListener('resize', setSliderHeight);
                })();
            </script>
        </section>
        <?php
    }
}
