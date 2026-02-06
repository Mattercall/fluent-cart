<?php

namespace FluentCart\App\Services\Permission;

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
            if (method_exists($data, 'toArray')) {
                return static::maskCustomerEmails($data->toArray());
            }

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
}
