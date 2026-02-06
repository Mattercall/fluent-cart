<script setup>
import {useRoute} from 'vue-router';
import {computed} from "vue";
import translate from "@/utils/translator/Translator";

const route = useRoute();

const routes = computed(() => {
  const baseRoutes = [
    {
      name: translate('Dashboard'),
      components: 'dashboard'
    },
    {
      name: translate('Purchase History'),
      components: 'purchase-history'
    },
    {
      name: translate('Subscriptions'),
      components: 'subscriptions'
    },
    {
      name: translate('Licenses'),
      components: 'licenses'
    },
    {
      name: translate('Downloads'),
      components: 'downloads'
    },
    {
      name: translate('Profile'),
      components: 'profile'
    },
    {
      name: translate('Support Centre'),
      href: '/support',
      isExternalLink: true
    },
  ];

  return baseRoutes;
})

/**
 * Checks if a given route is currently active
 * based on the route name and path conditions.
 */
const isRouteActive = (tabRoute) => {
  const isRouteNameMatch = route.name === tabRoute.components;
  const isOrderRoute = route.fullPath.includes('/order');
  const isSubscriptionRoute = route.fullPath.includes('/subscription');
  const isLicenseRoute = route.fullPath.includes('/licenses');

  return isRouteNameMatch || (tabRoute.components === 'purchase-history' && isOrderRoute) ||
      (tabRoute.components === 'subscriptions' && isSubscriptionRoute) || (tabRoute.components === 'licenses' && isLicenseRoute);
};


</script>

<template>
  <div class="fct-customer-dashboard-navs" role="navigation" :aria-label="$t('Customer Dashboard Navigation')">
    <template v-for="(route, i) in routes" :key="i">
      <a
          v-if="route.isExternalLink"
          class="fct-customer-dashboard-nav-link"
          :href="route.href"
          role="link"
          :aria-label="route.name"
      >
        {{ route.name }}
      </a>

      <router-link
          v-else
          class="fct-customer-dashboard-nav-link"
          :to="{name: route.components }"
          :class="{'tab-item-active': isRouteActive(route)}"
          role="link"
          :aria-current="isRouteActive(route) ? 'page' : null"
          :aria-label="route.name"
      >
        {{ route.name }}
      </router-link>
    </template>
  </div>
</template>
