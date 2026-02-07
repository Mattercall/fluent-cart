<?php

namespace FluentCart\App\Modules\SaleBooster;

use FluentCart\Api\ModuleSettings;

class SaleBoosterInit
{
    public function register($app)
    {
        add_filter('fluent_cart/module_setting/fields', function ($fields, $args) {
            $fields['sale_booster'] = [
                'title'       => __('Sale Booster', 'fluent-cart'),
                'description' => __('Configure product-specific video links and supporting images shown under the main product image.', 'fluent-cart'),
                'type'        => 'component',
                'component'   => 'SaleBoosterSettings',
            ];

            return $fields;
        }, 10, 2);

        add_filter('fluent_cart/module_setting/default_values', function ($values, $args) {
            if (empty($values['sale_booster']['active'])) {
                $values['sale_booster']['active'] = 'no';
            }

            if (empty($values['sale_booster']['products']) || !is_array($values['sale_booster']['products'])) {
                $values['sale_booster']['products'] = [];
            }

            return $values;
        }, 10, 2);

        if (ModuleSettings::isActive('sale_booster')) {
            (new SaleBoosterBoot())->register();
        }
    }
}
