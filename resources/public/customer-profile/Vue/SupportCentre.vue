<template>
    <div class="fct-customer-dashboard fct-customer-dashboard-layout-width fct-support-centre">
        <div class="fct-support-centre-card">
            <h4 class="fct-customer-dashboard-title">{{ $t('Support Centre') }}</h4>
            <div ref="portalContainer" class="fct-support-centre-portal" v-html="supportPortalHtml"></div>
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
    },
    mounted() {
        this.renderPortalScripts();
    },
    updated() {
        this.renderPortalScripts();
    },
    methods: {
        renderPortalScripts() {
            const portalContainer = this.$refs.portalContainer;

            if (!portalContainer) {
                return;
            }

            const scriptElements = portalContainer.querySelectorAll('script');

            scriptElements.forEach((oldScript) => {
                if (oldScript.dataset.fctExecuted === 'yes') {
                    return;
                }

                const newScript = document.createElement('script');

                Array.from(oldScript.attributes).forEach((attribute) => {
                    newScript.setAttribute(attribute.name, attribute.value);
                });

                if (!oldScript.src) {
                    newScript.textContent = oldScript.textContent;
                }

                newScript.dataset.fctExecuted = 'yes';
                oldScript.replaceWith(newScript);
            });
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

.fct-support-centre-portal :deep(iframe),
.fct-support-centre-portal :deep(embed),
.fct-support-centre-portal :deep(object) {
    width: 100%;
    max-width: 100%;
}

@media (max-width: 767px) {
    .fct-support-centre-card {
        padding: 16px;
        border-radius: 10px;
    }
}
</style>
