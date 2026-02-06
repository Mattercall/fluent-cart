<template>
    <div class="fct-customer-dashboard fct-customer-dashboard-layout-width fct-support-centre">
        <div class="fct-support-centre-card">
            <h4 class="fct-customer-dashboard-title">{{ $t('Support Centre') }}</h4>
            <div class="fct-support-centre-portal" v-html="supportPortalHtml"></div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'SupportCentre',
    computed: {
        supportPortalHtml() {
            return this.appVars.support_portal_html || '';
        }
    }
}
</script>

<style scoped>
.fct-support-centre-card {
    width: 100%;
    background: var(--fct-card-bg, #fff);
    border: 1px solid var(--fct-border-color, #e5e7eb);
    border-radius: 12px;
    padding: 20px;
    box-sizing: border-box;
    overflow: hidden;
}

.fct-support-centre-portal {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
}

.fct-support-centre-portal :deep(*) {
    max-width: 100%;
    box-sizing: border-box;
}

@media (max-width: 767px) {
    .fct-support-centre-card {
        padding: 16px;
        border-radius: 10px;
    }
}
</style>
