<?php

namespace FluentCart\App\Services\Permission;

use FluentCart\Framework\Database\Orm\Model;
use FluentCart\Framework\Pagination\AbstractCursorPaginator;
use FluentCart\Framework\Pagination\AbstractPaginator;
use FluentCart\Framework\Support\Collection;

class ShopManagerPrivacyService
{
    public static function shouldMaskCustomerEmails(): bool
    {
        return PermissionManager::getShopRole(get_current_user_id()) === 'shop_manager';
    }

    public static function maskEmail($email): string
    {
        $email = sanitize_email((string)$email);

        if (empty($email) || strpos($email, '@') === false) {
            return __('Hidden', 'fluent-cart');
        }

        [$local, $domain] = explode('@', $email, 2);

        $localPrefix = substr($local, 0, 1);
        $domainPrefix = substr($domain, 0, 1);

        return sprintf('%s***@%s***', $localPrefix ?: '*', $domainPrefix ?: '*');
    }

    public static function maskCustomerEmails($data)
    {
        if (!static::shouldMaskCustomerEmails()) {
            return $data;
        }

        if ($data instanceof Model) {
            return static::maskModelCustomerEmails($data);
        }

        if ($data instanceof Collection) {
            return $data->map(function ($item) {
                return static::maskCustomerEmails($item);
            });
        }

        if ($data instanceof AbstractPaginator || $data instanceof AbstractCursorPaginator) {
            $maskedItems = static::maskCustomerEmails($data->getCollection());

            if ($maskedItems instanceof Collection) {
                $data->setCollection($maskedItems);
            }

            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($key) && stripos($key, 'email') !== false) {
                    $data[$key] = static::maskEmail($value);
                    continue;
                }

                $data[$key] = static::maskCustomerEmails($value);
            }

            return $data;
        }

        if (is_object($data)) {
            foreach ($data as $key => $value) {
                if (is_string($key) && stripos($key, 'email') !== false) {
                    $data->{$key} = static::maskEmail($value);
                    continue;
                }

                $data->{$key} = static::maskCustomerEmails($value);
            }
        }

        return $data;
    }

    protected static function maskModelCustomerEmails(Model $model): Model
    {
        foreach ($model->getAttributes() as $key => $value) {
            if (is_string($key) && stripos($key, 'email') !== false) {
                $model->setAttribute($key, static::maskEmail($value));
            }
        }

        foreach ($model->getRelations() as $relation => $value) {
            $model->setRelation($relation, static::maskCustomerEmails($value));
        }

        return $model;
    }
}
