<?php

namespace FluentCart\App\Http\Controllers;

use FluentCart\App\CPT\FluentProducts;
use FluentCart\App\CPT\ProductFaq as ProductFaqCPT;
use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductFaq;
use FluentCart\Framework\Http\Request\Request;
use FluentCart\Framework\Support\Arr;

class ProductFaqController extends Controller
{
    public function index(Request $request)
    {
        $search = sanitize_text_field($request->get('search', ''));
        $productId = absint($request->get('product_id'));

        $query = ProductFaq::query()->orderBy('ID', 'DESC');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('post_title', 'like', '%' . $search . '%')
                    ->orWhere('post_content', 'like', '%' . $search . '%');
            });
        }

        $faqs = $query->get()->map(function ($faq) {
            return $this->formatFaq($faq);
        })->values();

        if ($productId) {
            $faqs = $faqs->filter(function ($faq) use ($productId) {
                return absint(Arr::get($faq, 'product_id')) === $productId;
            })->values();
        }

        return [
            'faqs' => $faqs,
            'total' => $faqs->count()
        ];
    }

    public function store(Request $request)
    {
        $payload = $this->sanitizePayload($request->all());

        if (is_wp_error($payload)) {
            return $this->sendError(['message' => $payload->get_error_message()], 422);
        }

        $postId = wp_insert_post([
            'post_type' => ProductFaqCPT::CPT_NAME,
            'post_status' => 'publish',
            'post_title' => $payload['question'],
            'post_content' => $payload['answer']
        ], true);

        if (is_wp_error($postId)) {
            return $this->sendError(['message' => $postId->get_error_message()], 422);
        }

        $this->saveMeta($postId, $payload);

        return [
            'message' => __('FAQ created successfully', 'fluent-cart'),
            'faq' => $this->formatFaq(ProductFaq::find($postId))
        ];
    }

    public function update(Request $request, $id)
    {
        $id = absint($id);
        $faq = ProductFaq::find($id);

        if (!$faq) {
            return $this->sendError(['message' => __('FAQ not found', 'fluent-cart')], 404);
        }

        $payload = $this->sanitizePayload($request->all());

        if (is_wp_error($payload)) {
            return $this->sendError(['message' => $payload->get_error_message()], 422);
        }

        wp_update_post([
            'ID' => $id,
            'post_title' => $payload['question'],
            'post_content' => $payload['answer']
        ]);

        $this->saveMeta($id, $payload);

        return [
            'message' => __('FAQ updated successfully', 'fluent-cart'),
            'faq' => $this->formatFaq(ProductFaq::find($id))
        ];
    }

    public function delete(Request $request, $id)
    {
        wp_delete_post(absint($id), true);

        return ['message' => __('FAQ deleted successfully', 'fluent-cart')];
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

    protected function sanitizePayload($data)
    {
        $question = sanitize_text_field(Arr::get($data, 'question', ''));
        $answer = sanitize_textarea_field(Arr::get($data, 'answer', ''));
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

        if (!$question || !$answer || !$productId) {
            return new \WP_Error('validation_error', __('Question, answer and product are required.', 'fluent-cart'));
        }

        $product = get_post($productId);

        if (!$product || $product->post_type !== FluentProducts::CPT_NAME) {
            return new \WP_Error('validation_error', __('Invalid product selected.', 'fluent-cart'));
        }

        return [
            'question' => $question,
            'answer' => $answer,
            'enabled' => $enabled,
            'sort_order' => $sortOrder,
            'product_id' => $productId,
            'product_slug' => $product->post_name
        ];
    }

    protected function saveMeta($postId, $payload)
    {
        foreach ($payload as $key => $value) {
            update_post_meta($postId, '_fc_faq_' . $key, $value);
        }
    }

    protected function formatFaq($faq)
    {
        if (!$faq) {
            return [];
        }

        return [
            'id' => absint($faq->ID),
            'question' => get_post_meta($faq->ID, '_fc_faq_question', true) ?: $faq->post_title,
            'answer' => get_post_meta($faq->ID, '_fc_faq_answer', true) ?: $faq->post_content,
            'enabled' => get_post_meta($faq->ID, '_fc_faq_enabled', true) ?: 'yes',
            'sort_order' => intval(get_post_meta($faq->ID, '_fc_faq_sort_order', true)),
            'product_id' => absint(get_post_meta($faq->ID, '_fc_faq_product_id', true)),
            'product_slug' => get_post_meta($faq->ID, '_fc_faq_product_slug', true)
        ];
    }
}
