<?php

namespace FluentCart\App\CPT;

class CustomerReview
{
    const CPT_NAME = 'fcustomer-review';

    public function register()
    {
        add_action('init', function () {
            register_post_type(self::CPT_NAME, [
                'labels' => [
                    'name' => __('Customer Reviews', 'fluent-cart'),
                    'singular_name' => __('Customer Review', 'fluent-cart')
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
