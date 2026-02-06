<?php

namespace FluentCart\App\CPT;

class ProductFaq
{
    const CPT_NAME = 'fproduct-faq';

    public function register()
    {
        add_action('init', function () {
            register_post_type(self::CPT_NAME, [
                'labels' => [
                    'name' => __('Product FAQs', 'fluent-cart'),
                    'singular_name' => __('Product FAQ', 'fluent-cart')
                ],
                'public' => false,
                'show_ui' => false,
                'show_in_menu' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'has_archive' => false,
                'supports' => ['title', 'editor'],
                'capability_type' => 'post'
            ]);
        });
    }
}
