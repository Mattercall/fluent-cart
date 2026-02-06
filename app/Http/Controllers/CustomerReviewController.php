<?php

namespace FluentCart\App\Http\Controllers;

use FluentCart\App\CPT\CustomerReview as CustomerReviewCPT;
use FluentCart\App\CPT\FluentProducts;
use FluentCart\App\Models\CustomerReview;
use FluentCart\App\Models\Product;
use FluentCart\Framework\Http\Request\Request;
use FluentCart\Framework\Support\Arr;

class CustomerReviewController extends Controller
{
    public function index(Request $request)
    {
        $search = sanitize_text_field($request->get('search', ''));
        $productId = absint($request->get('product_id'));

        $query = CustomerReview::query()->orderBy('ID', 'DESC');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('post_title', 'like', '%' . $search . '%')
                    ->orWhere('post_content', 'like', '%' . $search . '%');
            });
        }

        $reviews = $query->get()->map(function ($review) {
            return $this->formatReview($review);
        })->values();

        if ($productId) {
            $reviews = $reviews->filter(function ($review) use ($productId) {
                return absint(Arr::get($review, 'product_id')) === $productId;
            })->values();
        }

        return [
            'reviews' => $reviews,
            'total'   => $reviews->count()
        ];
    }

    public function store(Request $request)
    {
        $payload = $this->sanitizePayload($request->all());
        if (is_wp_error($payload)) {
            return $this->sendError(['message' => $payload->get_error_message()], 422);
        }

        $postId = wp_insert_post([
            'post_type' => CustomerReviewCPT::CPT_NAME,
            'post_status' => 'publish',
            'post_title' => $payload['reviewer_name'],
            'post_content' => $payload['review_text']
        ], true);

        if (is_wp_error($postId)) {
            return $this->sendError(['message' => $postId->get_error_message()], 422);
        }

        $this->saveMeta($postId, $payload);

        return [
            'message' => __('Review created successfully', 'fluent-cart'),
            'review'  => $this->formatReview(CustomerReview::find($postId))
        ];
    }

    public function update(Request $request, $id)
    {
        $id = absint($id);
        $review = CustomerReview::find($id);
        if (!$review) {
            return $this->sendError(['message' => __('Review not found', 'fluent-cart')], 404);
        }

        $payload = $this->sanitizePayload($request->all(), $id);
        if (is_wp_error($payload)) {
            return $this->sendError(['message' => $payload->get_error_message()], 422);
        }

        wp_update_post([
            'ID' => $id,
            'post_title' => $payload['reviewer_name'],
            'post_content' => $payload['review_text']
        ]);

        $this->saveMeta($id, $payload);

        return [
            'message' => __('Review updated successfully', 'fluent-cart'),
            'review' => $this->formatReview(CustomerReview::find($id))
        ];
    }

    public function delete(Request $request, $id)
    {
        $id = absint($id);
        wp_delete_post($id, true);

        return ['message' => __('Review deleted successfully', 'fluent-cart')];
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_map('absint', (array)$request->get('ids', []));
        foreach ($ids as $id) {
            if ($id) {
                wp_delete_post($id, true);
            }
        }

        return ['message' => __('Selected reviews deleted successfully', 'fluent-cart')];
    }

    public function products()
    {
        $products = Product::query()
            ->published()
            ->orderBy('ID', 'DESC')
            ->limit(200)
            ->get(['ID', 'post_title', 'post_name']);

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

    public function export()
    {
        $reviews = CustomerReview::query()->orderBy('ID', 'DESC')->get()->map(function ($review) {
            return $this->formatReview($review);
        })->values();

        return ['reviews' => $reviews];
    }

    public function import(Request $request)
    {
        $reviews = (array)$request->get('reviews', []);

        foreach ($reviews as $reviewData) {
            $payload = $this->sanitizePayload((array)$reviewData);
            if (is_wp_error($payload)) {
                continue;
            }
            $postId = wp_insert_post([
                'post_type' => CustomerReviewCPT::CPT_NAME,
                'post_status' => 'publish',
                'post_title' => $payload['reviewer_name'],
                'post_content' => $payload['review_text']
            ], true);
            if (!is_wp_error($postId)) {
                $this->saveMeta($postId, $payload);
            }
        }

        return ['message' => __('Reviews imported successfully', 'fluent-cart')];
    }

    protected function sanitizePayload($data)
    {
        $reviewerName = sanitize_text_field(Arr::get($data, 'reviewer_name', ''));
        $country = sanitize_text_field(Arr::get($data, 'country', ''));
        $normalizedCountryCode = strtoupper($country);
        if (preg_match('/^[A-Z]{2}$/', $normalizedCountryCode)) {
            $country = $normalizedCountryCode;
        }
        $countryFlag = wp_kses_post(Arr::get($data, 'country_flag', ''));
        $rating = min(5, max(1, absint(Arr::get($data, 'rating', 5))));
        $reviewText = sanitize_textarea_field(Arr::get($data, 'review_text', ''));
        $enabled = Arr::get($data, 'enabled', 'yes') === 'no' ? 'no' : 'yes';
        $sortOrder = intval(Arr::get($data, 'sort_order', 0));
        $reviewTime = sanitize_text_field(Arr::get($data, 'review_time', current_time('mysql')));

        $productId = absint(Arr::get($data, 'product_id'));
        $productSlug = sanitize_title(Arr::get($data, 'product_slug', ''));

        if (!$productId && $productSlug) {
            $product = get_page_by_path($productSlug, OBJECT, FluentProducts::CPT_NAME);
            if ($product) {
                $productId = absint($product->ID);
            }
        }

        if (!$reviewerName || !$reviewText || !$productId) {
            return new \WP_Error('validation_error', __('Reviewer, review text and product are required.', 'fluent-cart'));
        }

        $product = get_post($productId);
        if (!$product || $product->post_type !== FluentProducts::CPT_NAME) {
            return new \WP_Error('validation_error', __('Invalid product selected.', 'fluent-cart'));
        }

        $reviewTimestamp = strtotime($reviewTime);
        if (!$reviewTimestamp) {
            $reviewTimestamp = current_time('timestamp', true);
        }

        return [
            'reviewer_name' => $reviewerName,
            'country' => $country,
            'country_flag' => $countryFlag,
            'rating' => $rating,
            'review_text' => $reviewText,
            'review_time' => gmdate('Y-m-d H:i:s', $reviewTimestamp),
            'enabled' => $enabled,
            'sort_order' => $sortOrder,
            'product_id' => $productId,
            'product_slug' => $product->post_name
        ];
    }

    protected function saveMeta($postId, $payload)
    {
        foreach ($payload as $key => $value) {
            update_post_meta($postId, '_fc_review_' . $key, $value);
        }
    }

    public function formatReview($review)
    {
        if (!$review) {
            return [];
        }

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
            'product_id' => absint(get_post_meta($review->ID, '_fc_review_product_id', true)),
            'product_slug' => get_post_meta($review->ID, '_fc_review_product_slug', true)
        ];
    }
}
