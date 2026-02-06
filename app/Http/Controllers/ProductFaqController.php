<?php

namespace FluentCart\App\Http\Controllers;

use FluentCart\App\CPT\FluentProducts;
use FluentCart\App\Models\Product;
use FluentCart\Framework\Http\Request\Request;
use FluentCart\Framework\Support\Arr;
use FluentCart\Framework\Support\Helper;

class ProductFaqController extends Controller
{
    const OPTION_KEY = 'fluent_cart_product_faqs';

    public function index(Request $request)
    {
        $search = sanitize_text_field($request->get('search', ''));
        $productId = absint($request->get('product_id'));
        $faqs = Helper::collect($this->getFaqItems());

        if ($search) {
            $faqs = $faqs->filter(function ($faq) use ($search) {
                return str_contains(strtolower(Arr::get($faq, 'question', '')), strtolower($search)) ||
                    str_contains(strtolower(wp_strip_all_tags(Arr::get($faq, 'answer', ''))), strtolower($search));
            });
        }

        if ($productId) {
            $faqs = $faqs->filter(function ($faq) use ($productId) {
                return absint(Arr::get($faq, 'product_id')) === $productId || Arr::get($faq, 'is_global') === 'yes';
            });
        }

        return [
            'faqs' => $faqs->sortBy([
                ['sort_order', 'asc'],
                ['id', 'desc']
            ])->values()->all(),
            'total' => $faqs->count()
        ];
    }

    public function store(Request $request)
    {
        $payload = $this->sanitizePayload($request->all());

        if (is_wp_error($payload)) {
            return $this->sendError(['message' => $payload->get_error_message()], 422);
        }

        $faqs = $this->getFaqItems();
        $payload['id'] = time() + wp_rand(1, 9999);
        $faqs[] = $payload;

        $this->saveFaqItems($faqs);

        return [
            'message' => __('FAQ created successfully', 'fluent-cart'),
            'faq' => $payload
        ];
    }

    public function update(Request $request, $id)
    {
        $id = absint($id);
        $payload = $this->sanitizePayload($request->all());

        if (is_wp_error($payload)) {
            return $this->sendError(['message' => $payload->get_error_message()], 422);
        }

        $faqs = $this->getFaqItems();
        $updated = false;

        foreach ($faqs as &$faq) {
            if (absint(Arr::get($faq, 'id')) === $id) {
                $payload['id'] = $id;
                $faq = $payload;
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            return $this->sendError(['message' => __('FAQ not found', 'fluent-cart')], 404);
        }

        $this->saveFaqItems($faqs);

        return [
            'message' => __('FAQ updated successfully', 'fluent-cart'),
            'faq' => $payload
        ];
    }

    public function delete(Request $request, $id)
    {
        $id = absint($id);
        $faqs = Helper::collect($this->getFaqItems())->reject(function ($faq) use ($id) {
            return absint(Arr::get($faq, 'id')) === $id;
        })->values()->all();

        $this->saveFaqItems($faqs);

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

    protected function getFaqItems()
    {
        $faqs = get_option(self::OPTION_KEY, []);

        if (!is_array($faqs)) {
            return [];
        }

        return Helper::collect($faqs)->map(function ($faq) {
            return [
                'id' => absint(Arr::get($faq, 'id')),
                'question' => sanitize_text_field(Arr::get($faq, 'question', '')),
                'answer' => wp_kses_post(Arr::get($faq, 'answer', '')),
                'product_id' => absint(Arr::get($faq, 'product_id')),
                'product_slug' => sanitize_title(Arr::get($faq, 'product_slug', '')),
                'sort_order' => intval(Arr::get($faq, 'sort_order', 0)),
                'enabled' => Arr::get($faq, 'enabled') === 'no' ? 'no' : 'yes',
                'is_global' => Arr::get($faq, 'is_global') === 'yes' ? 'yes' : 'no'
            ];
        })->filter(function ($faq) {
            return !empty($faq['id']) && !empty($faq['question']) && !empty(wp_strip_all_tags($faq['answer']));
        })->values()->all();
    }

    protected function saveFaqItems(array $faqs)
    {
        update_option(self::OPTION_KEY, array_values($faqs), false);
    }

    protected function sanitizePayload($data)
    {
        $question = sanitize_text_field(Arr::get($data, 'question', ''));
        $answer = wp_kses_post(Arr::get($data, 'answer', ''));
        $sortOrder = intval(Arr::get($data, 'sort_order', 0));
        $enabled = Arr::get($data, 'enabled', 'yes') === 'no' ? 'no' : 'yes';
        $isGlobal = Arr::get($data, 'is_global', 'no') === 'yes' ? 'yes' : 'no';

        $productId = absint(Arr::get($data, 'product_id'));
        $productSlug = sanitize_title(Arr::get($data, 'product_slug', ''));

        if ($isGlobal !== 'yes') {
            if (!$productId && $productSlug) {
                $product = get_page_by_path($productSlug, OBJECT, FluentProducts::CPT_NAME);
                if ($product) {
                    $productId = absint($product->ID);
                }
            }

            if (!$productId) {
                return new \WP_Error('validation_error', __('Please assign a product or mark this as Global FAQ.', 'fluent-cart'));
            }

            $product = get_post($productId);

            if (!$product || $product->post_type !== FluentProducts::CPT_NAME) {
                return new \WP_Error('validation_error', __('Invalid product selected.', 'fluent-cart'));
            }

            $productSlug = $product->post_name;
        } else {
            $productId = 0;
            $productSlug = '';
        }

        if (!$question || !wp_strip_all_tags($answer)) {
            return new \WP_Error('validation_error', __('Question and answer are required.', 'fluent-cart'));
        }

        return [
            'question' => $question,
            'answer' => $answer,
            'product_id' => $productId,
            'product_slug' => $productSlug,
            'sort_order' => $sortOrder,
            'enabled' => $enabled,
            'is_global' => $isGlobal
        ];
    }
}
