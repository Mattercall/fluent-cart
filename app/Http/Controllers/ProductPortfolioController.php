<?php

namespace FluentCart\App\Http\Controllers;

use FluentCart\App\CPT\FluentProducts;
use FluentCart\App\CPT\ProductPortfolio as ProductPortfolioCPT;
use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductPortfolio;
use FluentCart\Framework\Http\Request\Request;
use FluentCart\Framework\Support\Arr;

class ProductPortfolioController extends Controller
{
    public function index(Request $request)
    {
        $search = sanitize_text_field($request->get('search', ''));
        $productId = absint($request->get('product_id'));

        $query = ProductPortfolio::query()->orderBy('ID', 'DESC');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('post_title', 'like', '%' . $search . '%')
                    ->orWhere('post_content', 'like', '%' . $search . '%');
            });
        }

        $entries = $query->get()->map(function ($entry) {
            return $this->formatEntry($entry);
        })->values();

        if ($productId) {
            $entries = $entries->filter(function ($entry) use ($productId) {
                return absint(Arr::get($entry, 'product_id')) === $productId;
            })->values();
        }

        return ['entries' => $entries, 'total' => $entries->count()];
    }

    public function store(Request $request)
    {
        $payload = $this->sanitizePayload($request->all());
        if (is_wp_error($payload)) {
            return $this->sendError(['message' => $payload->get_error_message()], 422);
        }

        $postId = wp_insert_post([
            'post_type' => ProductPortfolioCPT::CPT_NAME,
            'post_status' => 'publish',
            'post_title' => $payload['title'],
            'post_content' => $payload['full_description']
        ], true);

        if (is_wp_error($postId)) {
            return $this->sendError(['message' => $postId->get_error_message()], 422);
        }

        $this->saveMeta($postId, $payload);

        return [
            'message' => __('Portfolio entry created successfully', 'fluent-cart'),
            'entry' => $this->formatEntry(ProductPortfolio::find($postId))
        ];
    }

    public function update(Request $request, $id)
    {
        $id = absint($id);
        $entry = ProductPortfolio::find($id);

        if (!$entry) {
            return $this->sendError(['message' => __('Portfolio entry not found', 'fluent-cart')], 404);
        }

        $payload = $this->sanitizePayload($request->all());
        if (is_wp_error($payload)) {
            return $this->sendError(['message' => $payload->get_error_message()], 422);
        }

        wp_update_post([
            'ID' => $id,
            'post_title' => $payload['title'],
            'post_content' => $payload['full_description']
        ]);

        $this->saveMeta($id, $payload);

        return [
            'message' => __('Portfolio entry updated successfully', 'fluent-cart'),
            'entry' => $this->formatEntry(ProductPortfolio::find($id))
        ];
    }

    public function delete(Request $request, $id)
    {
        wp_delete_post(absint($id), true);

        return ['message' => __('Portfolio entry deleted successfully', 'fluent-cart')];
    }

    public function products()
    {
        $products = Product::query()->published()->orderBy('ID', 'DESC')->limit(200)->get(['ID', 'post_title', 'post_name']);

        return [
            'products' => $products->map(function ($product) {
                return [
                    'id' => absint($product->ID),
                    'title' => $product->post_title,
                    'slug' => $product->post_name
                ];
            })->values()
        ];
    }

    protected function sanitizePayload($data)
    {
        $title = sanitize_text_field(Arr::get($data, 'title', ''));
        $imageUrl = esc_url_raw(Arr::get($data, 'image_url', ''));
        $smallDescription = sanitize_textarea_field(Arr::get($data, 'small_description', ''));
        $fullDescription = sanitize_textarea_field(Arr::get($data, 'full_description', ''));
        $priceRange = sanitize_text_field(Arr::get($data, 'price_range', ''));
        $date = sanitize_text_field(Arr::get($data, 'date', ''));
        $enabled = Arr::get($data, 'enabled', 'yes') === 'no' ? 'no' : 'yes';
        $sortOrder = intval(Arr::get($data, 'sort_order', 0));

        $productId = absint(Arr::get($data, 'product_id'));
        $productSlug = sanitize_title(Arr::get($data, 'product_slug', ''));

        if (!$productId && $productSlug) {
            $product = get_page_by_path($productSlug, OBJECT, FluentProducts::CPT_NAME);
            if ($product) {
                $productId = absint($product->ID);
            }
        }

        if (!$title || !$productId) {
            return new \WP_Error('validation_error', __('Title and product are required.', 'fluent-cart'));
        }

        $product = get_post($productId);

        if (!$product || $product->post_type !== FluentProducts::CPT_NAME) {
            return new \WP_Error('validation_error', __('Invalid product selected.', 'fluent-cart'));
        }

        return [
            'title' => $title,
            'image_url' => $imageUrl,
            'small_description' => $smallDescription,
            'full_description' => $fullDescription,
            'price_range' => $priceRange,
            'date' => $date,
            'enabled' => $enabled,
            'sort_order' => $sortOrder,
            'product_id' => $productId,
            'product_slug' => $product->post_name
        ];
    }

    protected function saveMeta($postId, $payload)
    {
        foreach ($payload as $key => $value) {
            update_post_meta($postId, '_fc_portfolio_' . $key, $value);
        }
    }

    protected function formatEntry($entry)
    {
        if (!$entry) {
            return [];
        }

        return [
            'id' => absint($entry->ID),
            'title' => get_post_meta($entry->ID, '_fc_portfolio_title', true) ?: $entry->post_title,
            'image_url' => get_post_meta($entry->ID, '_fc_portfolio_image_url', true),
            'small_description' => get_post_meta($entry->ID, '_fc_portfolio_small_description', true),
            'full_description' => get_post_meta($entry->ID, '_fc_portfolio_full_description', true) ?: $entry->post_content,
            'price_range' => get_post_meta($entry->ID, '_fc_portfolio_price_range', true),
            'date' => get_post_meta($entry->ID, '_fc_portfolio_date', true),
            'enabled' => get_post_meta($entry->ID, '_fc_portfolio_enabled', true) ?: 'yes',
            'sort_order' => intval(get_post_meta($entry->ID, '_fc_portfolio_sort_order', true)),
            'product_id' => absint(get_post_meta($entry->ID, '_fc_portfolio_product_id', true)),
            'product_slug' => get_post_meta($entry->ID, '_fc_portfolio_product_slug', true)
        ];
    }
}
