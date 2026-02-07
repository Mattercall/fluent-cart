<?php

namespace FluentCart\App\Http\Controllers;

use FluentCart\App\CPT\FluentProducts;
use FluentCart\App\Models\Product;
use FluentCart\Framework\Http\Request\Request;

class ProductPromotionalSectionController extends Controller
{
    public function products()
    {
        $products = Product::query()
            ->published()
            ->orderBy('ID', 'DESC')
            ->limit(200)
            ->get(['ID', 'post_title']);

        return [
            'products' => $products->map(function ($product) {
                return [
                    'id'    => absint($product->ID),
                    'title' => $product->post_title,
                ];
            })->values()
        ];
    }

    public function get(Request $request)
    {
        $productId = absint($request->get('product_id'));

        if (!$productId) {
            return ['settings' => []];
        }

        $product = get_post($productId);
        if (!$product || $product->post_type !== FluentProducts::CPT_NAME) {
            return $this->sendError([
                'message' => __('Invalid product selected.', 'fluent-cart')
            ], 422);
        }

        return [
            'settings' => $this->getPromotionalSettings($productId)
        ];
    }

    public function save(Request $request)
    {
        $productId = absint($request->get('product_id'));

        $product = get_post($productId);
        if (!$product || $product->post_type !== FluentProducts::CPT_NAME) {
            return $this->sendError([
                'message' => __('Invalid product selected.', 'fluent-cart')
            ], 422);
        }

        $sections = $this->sanitizeSections($request->get('sections', []));

        update_post_meta($productId, '_fc_product_promotional_section', $sections);

        return [
            'message'  => __('Promotional section saved successfully.', 'fluent-cart'),
            'settings' => $this->getPromotionalSettings($productId)
        ];
    }

    private function getPromotionalSettings($productId)
    {
        $settings = get_post_meta($productId, '_fc_product_promotional_section', true);

        if (!is_array($settings)) {
            $settings = [];
        }

        // Keep backward compatibility for previously saved single section array.
        if (isset($settings['image']) || isset($settings['heading']) || isset($settings['description'])) {
            $settings = [$settings];
        }

        return $this->sanitizeSections($settings);
    }

    private function sanitizeSections($sections)
    {
        if (!is_array($sections)) {
            return [];
        }

        $sanitizedSections = [];

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $image = $section['image'] ?? [];

            $sanitizedSection = [
                'image'       => [
                    'id'    => absint(is_array($image) ? ($image['id'] ?? 0) : 0),
                    'url'   => esc_url_raw(is_array($image) ? ($image['url'] ?? '') : ''),
                    'title' => sanitize_text_field(is_array($image) ? ($image['title'] ?? '') : ''),
                ],
                'heading'     => sanitize_text_field($section['heading'] ?? ''),
                'description' => sanitize_textarea_field($section['description'] ?? ''),
            ];

            if (empty($sanitizedSection['image']['url']) && empty($sanitizedSection['heading']) && empty($sanitizedSection['description'])) {
                continue;
            }

            $sanitizedSections[] = $sanitizedSection;
        }

        return array_values($sanitizedSections);
    }
}
