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
            $imageUrl = get_post_meta($entry->ID, '_fc_portfolio_image_url', true);
            $images = array_values(array_filter(array_map('trim', preg_split('/[,\n\r]+/', (string) $imageUrl))));

            return [
                'id' => absint($entry->ID),
                'title' => get_post_meta($entry->ID, '_fc_portfolio_title', true) ?: $entry->post_title,
                'image_url' => $imageUrl,
                'images' => $images,
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
        <section class="fct-product-portfolio" aria-label="<?php echo esc_attr__('Client Success Stories', 'fluent-cart'); ?>">
            <style>
                .fct-product-portfolio{margin:30px 0 0;padding:24px;border:1px solid #e4e7ec;border-radius:16px;background:#fff;box-shadow:0 10px 35px -30px rgba(15,23,42,.4)}
                .fct-portfolio-title{margin:0 0 6px;font-size:28px;font-weight:700;color:#101828}
                .fct-portfolio-subtitle{margin:0 0 18px;color:#667085;font-size:15px;line-height:1.5}
                .fct-portfolio-slider{position:relative;overflow:hidden}
                .fct-portfolio-track{display:flex;transition:transform .45s ease}
                .fct-portfolio-slide{min-width:100%;width:100%}
                .fct-portfolio-grid{display:grid;gap:14px;grid-template-columns:repeat(4,minmax(0,1fr))}
                .fct-portfolio-card{border:1px solid #eaecf0;border-radius:14px;padding:14px;background:#fff;cursor:pointer;display:flex;flex-direction:column;gap:10px;height:100%;transition:all .2s ease}
                .fct-portfolio-card:hover{border-color:#d0d5dd;transform:translateY(-2px);box-shadow:0 14px 24px -24px rgba(2,6,23,.85)}
                .fct-portfolio-card img{width:100%;height:164px;object-fit:cover;border-radius:10px;background:#f4f4f5}
                .fct-portfolio-card h4{margin:0;font-size:16px;color:#111827}
                .fct-portfolio-card p{margin:0;color:#475467;font-size:14px;line-height:1.45}
                .fct-portfolio-meta{font-size:13px;color:#667085;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
                .fct-portfolio-open{margin-top:auto;color:#344054;font-size:12px;font-weight:600;letter-spacing:.02em;text-transform:uppercase}
                .fct-portfolio-dots{display:flex;justify-content:center;gap:6px;margin-top:12px}
                .fct-portfolio-dot{border:0;height:8px;width:8px;border-radius:999px;background:#cbd5e1;cursor:pointer}
                .fct-portfolio-dot.is-active{width:22px;background:#111827}
                .fct-portfolio-modal{position:fixed;inset:0;background:rgba(16,24,40,.55);display:none;align-items:center;justify-content:center;z-index:999999;padding:16px}
                .fct-portfolio-modal.is-open{display:flex}
                .fct-portfolio-modal-inner{background:#fff;border-radius:18px;max-width:920px;width:100%;max-height:90vh;overflow:auto;position:relative;box-shadow:0 22px 60px -38px rgba(2,6,23,.95)}
                .fct-portfolio-modal-close{position:absolute;top:14px;right:16px;border:1px solid #d0d5dd;background:#fff;color:#101828;height:34px;width:34px;border-radius:999px;font-size:22px;line-height:1;cursor:pointer;z-index:2}
                .fct-portfolio-modal-content{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,.9fr);min-height:100%}
                .fct-portfolio-modal-gallery{padding:22px;background:#f8fafc;border-right:1px solid #eaecf0}
                .fct-portfolio-main-image{width:100%;max-height:360px;object-fit:cover;border-radius:12px;background:#e4e7ec}
                .fct-portfolio-thumbs{display:grid;grid-template-columns:repeat(auto-fill,minmax(68px,1fr));gap:8px;margin-top:10px}
                .fct-portfolio-thumb{border:2px solid transparent;border-radius:10px;padding:0;overflow:hidden;background:#fff;cursor:pointer}
                .fct-portfolio-thumb.is-active{border-color:#344054}
                .fct-portfolio-thumb img{display:block;width:100%;height:60px;object-fit:cover}
                .fct-portfolio-modal-details{padding:24px}
                .fct-portfolio-modal-eyebrow{display:inline-block;font-size:11px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#344054;background:#f2f4f7;border-radius:999px;padding:6px 10px;margin-bottom:12px}
                .fct-portfolio-modal-details h3{margin:0 0 8px;font-size:26px;line-height:1.2;color:#111827}
                .fct-portfolio-summary{margin:0 0 16px;color:#475467;font-size:14px;line-height:1.55}
                .fct-portfolio-modal-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-bottom:16px}
                .fct-portfolio-modal-meta-item{border:1px solid #eaecf0;border-radius:10px;padding:10px;background:#fff}
                .fct-portfolio-modal-meta-item small{display:block;color:#667085;font-size:11px;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em}
                .fct-portfolio-modal-meta-item strong{display:block;font-size:14px;color:#101828;line-height:1.4}
                .fct-portfolio-modal-body{color:#1f2937;line-height:1.62;font-size:15px}
                .fct-portfolio-modal-body p:first-child{margin-top:0}
                @media (max-width:991px){.fct-portfolio-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
                @media (max-width:860px){.fct-portfolio-modal-content{grid-template-columns:1fr}.fct-portfolio-modal-gallery{border-right:0;border-bottom:1px solid #eaecf0}.fct-portfolio-main-image{max-height:300px}}
                @media (max-width:640px){.fct-portfolio-grid{grid-template-columns:1fr}.fct-product-portfolio{padding:18px}.fct-portfolio-title{font-size:22px}.fct-portfolio-modal-meta{grid-template-columns:1fr}.fct-portfolio-modal-details{padding:20px}}
            </style>
            <h3 class="fct-portfolio-title"><?php echo esc_html__('Client Success Stories', 'fluent-cart'); ?></h3>
            <p class="fct-portfolio-subtitle"><?php echo esc_html__('Explore recent client projects, outcomes, budgets, and implementation details.', 'fluent-cart'); ?></p>
            <div class="fct-portfolio-slider" data-fct-portfolio-slider>
                <div class="fct-portfolio-track" data-fct-portfolio-track>
                    <?php foreach ($pages as $slide): ?>
                        <div class="fct-portfolio-slide">
                            <div class="fct-portfolio-grid">
                                <?php foreach ($slide as $item): ?>
                                    <article class="fct-portfolio-card" data-fct-portfolio='<?php echo wp_json_encode($item); ?>'>
                                        <?php $coverImage = !empty($item['images']) ? $item['images'][0] : $item['image_url']; ?>
                                        <?php if (!empty($coverImage)): ?><img src="<?php echo esc_url($coverImage); ?>" alt="<?php echo esc_attr($item['title']); ?>"/><?php endif; ?>
                                        <h4><?php echo esc_html($item['title']); ?></h4>
                                        <p><?php echo esc_html($item['small_description']); ?></p>
                                        <div class="fct-portfolio-meta">
                                            <span><?php echo esc_html($item['price_range']); ?></span>
                                            <span><?php echo esc_html($item['date']); ?></span>
                                        </div>
                                        <span class="fct-portfolio-open"><?php echo esc_html__('View case details', 'fluent-cart'); ?></span>
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
                    <button type="button" class="fct-portfolio-modal-close" data-fct-portfolio-close aria-label="<?php echo esc_attr__('Close dialog', 'fluent-cart'); ?>">×</button>
                    <div class="fct-portfolio-modal-content">
                        <div class="fct-portfolio-modal-gallery">
                            <img class="fct-portfolio-main-image" data-fct-p-image src="" alt="" style="display:none"/>
                            <div class="fct-portfolio-thumbs" data-fct-p-thumbs></div>
                        </div>
                        <div class="fct-portfolio-modal-details">
                            <span class="fct-portfolio-modal-eyebrow"><?php echo esc_html__('Client Success Story', 'fluent-cart'); ?></span>
                            <h3 data-fct-p-title></h3>
                            <p class="fct-portfolio-summary" data-fct-p-summary></p>
                            <div class="fct-portfolio-modal-meta">
                                <div class="fct-portfolio-modal-meta-item"><small><?php echo esc_html__('Price range', 'fluent-cart'); ?></small><strong data-fct-p-price></strong></div>
                                <div class="fct-portfolio-modal-meta-item"><small><?php echo esc_html__('Project date', 'fluent-cart'); ?></small><strong data-fct-p-date></strong></div>
                            </div>
                            <div class="fct-portfolio-modal-body" data-fct-p-full></div>
                        </div>
                    </div>
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
                    var modalImage=modal.querySelector('[data-fct-p-image]');
                    var modalThumbs=modal.querySelector('[data-fct-p-thumbs]');
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
                    var escapeHtml=function(value){
                        var div=document.createElement('div');
                        div.textContent=value||'';
                        return div.innerHTML;
                    };
                    var setModalImage=function(url){
                        if(!url){modalImage.style.display='none';modalImage.src='';return;}
                        modalImage.src=url;modalImage.style.display='block';
                    };
                    root.querySelectorAll('.fct-portfolio-card').forEach(function(card){
                        card.addEventListener('click',function(){
                            var data={};try{data=JSON.parse(card.getAttribute('data-fct-portfolio')||'{}')}catch(e){}
                            var images=Array.isArray(data.images)?data.images.filter(Boolean):[];
                            if(!images.length&&data.image_url){images=[data.image_url];}
                            modal.querySelector('[data-fct-p-title]').textContent=data.title||'';
                            modal.querySelector('[data-fct-p-summary]').textContent=data.small_description||'';
                            modal.querySelector('[data-fct-p-price]').textContent=data.price_range||'—';
                            modal.querySelector('[data-fct-p-date]').textContent=data.date||'—';
                            modal.querySelector('[data-fct-p-full]').innerHTML=(data.full_description||'').trim() ? data.full_description : '<p>'+escapeHtml('<?php echo esc_js(__('No additional details provided.', 'fluent-cart')); ?>')+'</p>';
                            if(images.length){
                                setModalImage(images[0]);
                                modalThumbs.innerHTML=images.map(function(url,idx){
                                    return '<button type="button" class="fct-portfolio-thumb'+(idx===0?' is-active':'')+'" data-fct-thumb="'+idx+'"><img src="'+escapeHtml(url)+'" alt=""></button>';
                                }).join('');
                            }else{
                                setModalImage('');
                                modalThumbs.innerHTML='';
                            }
                            modal.classList.add('is-open');
                            document.body.style.overflow='hidden';
                        });
                    });
                    modalThumbs.addEventListener('click',function(e){
                        var thumb=e.target.closest('[data-fct-thumb]');
                        if(!thumb){return;}
                        var img=thumb.querySelector('img');
                        if(!img){return;}
                        setModalImage(img.getAttribute('src'));
                        modalThumbs.querySelectorAll('.fct-portfolio-thumb').forEach(function(item){item.classList.remove('is-active');});
                        thumb.classList.add('is-active');
                    });
                    var closeModal=function(){modal.classList.remove('is-open');document.body.style.overflow='';};
                    root.querySelector('[data-fct-portfolio-close]').addEventListener('click',closeModal);
                    modal.addEventListener('click',function(e){if(e.target===modal){closeModal();}});
                    document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal.classList.contains('is-open')){closeModal();}});
                })();
            </script>
        </section>
        <?php

        return $content . ob_get_clean();
    }
}
