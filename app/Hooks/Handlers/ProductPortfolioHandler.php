<?php

namespace FluentCart\App\Hooks\Handlers;

use FluentCart\App\CPT\FluentProducts;
use FluentCart\App\Models\ProductPortfolio;

class ProductPortfolioHandler
{
    public function register()
    {
        add_filter('the_content', [$this, 'prependPortfolioToSingleProductContent'], 30);
    }

    public function prependPortfolioToSingleProductContent($content)
    {
        global $post;
        global $wp_query;

        if (!$post || !$wp_query || !$wp_query->is_main_query() || $post->post_type !== FluentProducts::CPT_NAME) {
            return $content;
        }

        $items = ProductPortfolio::query()->orderBy('ID', 'DESC')->get()->map(function ($entry) {
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
                'product_id' => absint(get_post_meta($entry->ID, '_fc_portfolio_product_id', true))
            ];
        })->filter(function ($item) use ($post) {
            return $item['enabled'] === 'yes' && absint($item['product_id']) === absint($post->ID);
        })->sortBy('sort_order')->values();

        if (!$items->count()) {
            return $content;
        }

        $pages = array_chunk($items->all(), 4);

        ob_start();
        ?>
        <section class="fct-product-portfolio" aria-label="<?php echo esc_attr__('Portfolio', 'fluent-cart'); ?>">
            <style>
                .fct-product-portfolio{margin:28px 0;padding:20px;border:1px solid #e4e7ec;border-radius:16px;background:#fff}
                .fct-portfolio-title{margin:0 0 16px;font-size:26px;font-weight:700;color:#101828}
                .fct-portfolio-slider{position:relative;overflow:hidden}
                .fct-portfolio-track{display:flex;transition:transform .45s ease}
                .fct-portfolio-slide{min-width:100%;width:100%}
                .fct-portfolio-grid{display:grid;gap:12px;grid-template-columns:repeat(4,minmax(0,1fr))}
                .fct-portfolio-card{border:1px solid #eaecf0;border-radius:12px;padding:12px;background:#fff;cursor:pointer;display:flex;flex-direction:column;gap:10px;height:100%}
                .fct-portfolio-card img{width:100%;height:150px;object-fit:cover;border-radius:8px;background:#f4f4f5}
                .fct-portfolio-card h4{margin:0;font-size:16px;color:#111827}
                .fct-portfolio-card p{margin:0;color:#475467;font-size:14px;line-height:1.45}
                .fct-portfolio-meta{font-size:13px;color:#667085;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
                .fct-portfolio-dots{display:flex;justify-content:center;gap:6px;margin-top:12px}
                .fct-portfolio-dot{border:0;height:8px;width:8px;border-radius:999px;background:#cbd5e1;cursor:pointer}
                .fct-portfolio-dot.is-active{width:22px;background:#111827}
                .fct-portfolio-modal{position:fixed;inset:0;background:rgba(16,24,40,.55);display:none;align-items:center;justify-content:center;z-index:999999;padding:16px}
                .fct-portfolio-modal.is-open{display:flex}
                .fct-portfolio-modal-inner{background:#fff;border-radius:14px;max-width:760px;width:100%;max-height:90vh;overflow:auto;padding:18px;position:relative}
                .fct-portfolio-modal-close{position:absolute;top:10px;right:12px;border:0;background:transparent;font-size:24px;cursor:pointer}
                .fct-portfolio-modal img{width:100%;max-height:320px;object-fit:cover;border-radius:8px;margin-bottom:12px}
                .fct-portfolio-modal-meta{display:flex;gap:16px;color:#667085;font-size:14px;margin-bottom:10px;flex-wrap:wrap}
                @media (max-width:991px){.fct-portfolio-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
                @media (max-width:640px){.fct-portfolio-grid{grid-template-columns:1fr}.fct-portfolio-title{font-size:22px}}
            </style>
            <h3 class="fct-portfolio-title"><?php echo esc_html__('Portfolio', 'fluent-cart'); ?></h3>
            <div class="fct-portfolio-slider" data-fct-portfolio-slider>
                <div class="fct-portfolio-track" data-fct-portfolio-track>
                    <?php foreach ($pages as $slide): ?>
                        <div class="fct-portfolio-slide">
                            <div class="fct-portfolio-grid">
                                <?php foreach ($slide as $item): ?>
                                    <article class="fct-portfolio-card" data-fct-portfolio='<?php echo wp_json_encode($item); ?>'>
                                        <?php if (!empty($item['image_url'])): ?><img src="<?php echo esc_url($item['image_url']); ?>" alt="<?php echo esc_attr($item['title']); ?>"/><?php endif; ?>
                                        <h4><?php echo esc_html($item['title']); ?></h4>
                                        <p><?php echo esc_html($item['small_description']); ?></p>
                                        <div class="fct-portfolio-meta">
                                            <span><?php echo esc_html($item['price_range']); ?></span>
                                            <span><?php echo esc_html($item['date']); ?></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="fct-portfolio-dots" data-fct-portfolio-dots></div>
            </div>
            <div class="fct-portfolio-modal" data-fct-portfolio-modal>
                <div class="fct-portfolio-modal-inner">
                    <button class="fct-portfolio-modal-close" data-fct-portfolio-close>×</button>
                    <img data-fct-p-image src="" alt="" style="display:none"/>
                    <h3 data-fct-p-title></h3>
                    <div class="fct-portfolio-modal-meta">
                        <span data-fct-p-price></span>
                        <span data-fct-p-date></span>
                    </div>
                    <div data-fct-p-full></div>
                </div>
            </div>
            <script>
                (function(){
                    var root=document.currentScript.previousElementSibling; if(!root){return;}
                    var slider=root.querySelector('[data-fct-portfolio-slider]');
                    var track=root.querySelector('[data-fct-portfolio-track]');
                    var slides=root.querySelectorAll('.fct-portfolio-slide');
                    var dotsWrap=root.querySelector('[data-fct-portfolio-dots]');
                    var modal=root.querySelector('[data-fct-portfolio-modal]');
                    var index=0,timer,total=slides.length;
                    if(!track||!total){return;}
                    var update=function(){track.style.transform='translateX(-'+(index*100)+'%)'; dotsWrap.querySelectorAll('.fct-portfolio-dot').forEach(function(d,i){d.classList.toggle('is-active',i===index);});};
                    var go=function(i){index=(i+total)%total;update();};
                    var initDots=function(){var h='';for(var i=0;i<total;i++){h+='<button type="button" class="fct-portfolio-dot'+(i===0?' is-active':'')+'" data-i="'+i+'"></button>';} dotsWrap.innerHTML=h;};
                    var restart=function(){if(timer){clearInterval(timer);} if(total>1){timer=setInterval(function(){go(index+1)},4500);}};
                    initDots(); update(); restart();
                    dotsWrap.addEventListener('click',function(e){var dot=e.target.closest('[data-i]');if(!dot){return;} go(parseInt(dot.getAttribute('data-i'),10)); restart();});
                    slider.addEventListener('mouseenter',function(){if(timer){clearInterval(timer);}});
                    slider.addEventListener('mouseleave',restart);
                    root.querySelectorAll('.fct-portfolio-card').forEach(function(card){
                        card.addEventListener('click',function(){
                            var data={};try{data=JSON.parse(card.getAttribute('data-fct-portfolio')||'{}')}catch(e){}
                            var img=modal.querySelector('[data-fct-p-image]');
                            modal.querySelector('[data-fct-p-title]').textContent=data.title||'';
                            modal.querySelector('[data-fct-p-price]').textContent=data.price_range||'';
                            modal.querySelector('[data-fct-p-date]').textContent=data.date||'';
                            modal.querySelector('[data-fct-p-full]').textContent=data.full_description||'';
                            if(data.image_url){img.src=data.image_url;img.style.display='block';}else{img.style.display='none';}
                            modal.classList.add('is-open');
                        });
                    });
                    root.querySelector('[data-fct-portfolio-close]').addEventListener('click',function(){modal.classList.remove('is-open');});
                    modal.addEventListener('click',function(e){if(e.target===modal){modal.classList.remove('is-open');}});
                })();
            </script>
        </section>
        <?php

        return ob_get_clean() . $content;
    }
}
