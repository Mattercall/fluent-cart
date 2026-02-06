<script setup>
import * as Card from '@/Bits/Components/Card/Card.js';
import {computed, onMounted, ref, watch} from 'vue';
import translate from '@/utils/translator/Translator';
import {VueDraggableNext} from 'vue-draggable-next';
import Attachments from '@/Bits/Components/Attachment/Attachments.vue';
import DynamicIcon from '@/Bits/Components/Icons/DynamicIcon.vue';

const props = defineProps({
  product: Object,
  productEditModel: Object
});

const maxSections = 5;

const createSection = () => ({
  order_key: `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
  title: '',
  description: '',
  image: [],
  button_text: '',
  link_url: ''
});

const sanitizeSection = (section) => {
  if (!section || typeof section !== 'object' || Array.isArray(section)) {
    return null;
  }

  return {
    ...createSection(),
    ...section,
    image: Array.isArray(section.image) ? section.image.slice(0, 1) : [],
    order_key: section.order_key || `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
  };
};

const getRawPromoSections = () => {
  const otherInfo = props.product?.detail?.other_info || {};
  return otherInfo.mattercall_promo_sections || otherInfo.promo_sections || [];
};

const promoSections = ref([]);

const canAddSection = computed(() => {
  return promoSections.value.length < maxSections;
});

const hydratePromoSections = () => {
  promoSections.value = (Array.isArray(getRawPromoSections()) ? getRawPromoSections() : [])
    .map((section) => sanitizeSection(section))
    .filter(Boolean)
    .slice(0, maxSections);
};

const ensurePromoSections = () => {
  if (!props.product.detail) {
    props.product.detail = {};
  }

  if (!props.product.detail.other_info) {
    props.product.detail.other_info = {};
  }

  const sanitizedSections = (Array.isArray(getRawPromoSections()) ? getRawPromoSections() : [])
    .map((section) => sanitizeSection(section))
    .filter(Boolean)
    .slice(0, maxSections);

  props.product.detail.other_info.mattercall_promo_sections = sanitizedSections;
  hydratePromoSections();
};

const normalizeSections = (sections) => {
  return (Array.isArray(sections) ? sections : [])
    .map((section) => sanitizeSection(section))
    .filter(Boolean)
    .slice(0, maxSections);
};

const syncChanges = () => {
  const sanitizedSections = normalizeSections(promoSections.value);

  promoSections.value = [...sanitizedSections];
  props.productEditModel.updateDetailOtherInfoField('mattercall_promo_sections', [...sanitizedSections]);
};

const addSection = () => {
  if (!canAddSection.value) {
    return;
  }

  const nextSections = normalizeSections([...promoSections.value, createSection()]);

  promoSections.value = [...nextSections];
  props.productEditModel.updateDetailOtherInfoField('mattercall_promo_sections', [...nextSections]);
};

const removeSection = (index) => {
  promoSections.value.splice(index, 1);
  promoSections.value = [...promoSections.value];
  syncChanges();
};

const updateSectionImage = (index, images) => {
  promoSections.value[index] = {
    ...promoSections.value[index],
    image: images.slice(0, 1)
  };
  syncChanges();
};

const onReorder = () => {
  syncChanges();
};

onMounted(() => {
  ensurePromoSections();
});

watch(() => props.product?.id, () => {
  ensurePromoSections();
});
</script>

<template>
  <Card.Container>
    <Card.Header :title="translate('Promotional Sections')">
      <template #action>
        <el-button :disabled="!canAddSection" @click="addSection">
          <DynamicIcon name="Plus"/>
          {{ translate('Add Section') }}
        </el-button>
      </template>
    </Card.Header>

    <Card.Body>
      <p class="fct-form-note mb-4">
        {{ translate('Add up to 5 promotional sections that will appear after the long description on the product page.') }}
      </p>

      <p v-if="promoSections.length >= maxSections" class="fct-form-note mb-4 text-orange-500">
        {{ translate('Maximum 5 promotional sections are allowed.') }}
      </p>

      <VueDraggableNext
        v-model="promoSections"
        item-key="order_key"
        handle=".promo-section-handle"
        @end="onReorder"
      >
        <template #item="{ element, index }">
          <div class="promo-section-item mb-4 border border-gray-200 rounded-lg p-4">
            <div class="flex justify-between items-center mb-4">
              <div class="flex items-center gap-2">
                <button type="button" class="promo-section-handle el-button is-text">
                  <DynamicIcon name="HamBurger"/>
                </button>
                <strong>{{ translate('Section') }} {{ index + 1 }}</strong>
              </div>
              <el-button text type="danger" @click="removeSection(index)">
                {{ translate('Remove') }}
              </el-button>
            </div>

            <el-form label-position="top">
              <el-form-item :label="translate('Title')" required>
                <el-input v-model="element.title" @input="syncChanges"/>
              </el-form-item>

              <el-form-item :label="translate('Text/Description')" required>
                <el-input
                  v-model="element.description"
                  type="textarea"
                  :rows="4"
                  @input="syncChanges"
                />
              </el-form-item>

              <el-form-item :label="translate('Image (optional)')">
                <Attachments
                  :attachments="element.image || []"
                  :multiple="false"
                  :title="translate('Upload Image')"
                  media-input-size="sm"
                  @mediaUploaded="images => updateSectionImage(index, images)"
                  @removeImage="() => updateSectionImage(index, [])"
                />
              </el-form-item>

              <el-form-item :label="translate('Button Text (optional)')">
                <el-input
                  v-model="element.button_text"
                  @input="syncChanges"
                />
              </el-form-item>

              <el-form-item :label="translate('Link URL (optional)')">
                <el-input
                  v-model="element.link_url"
                  placeholder="https://"
                  @input="syncChanges"
                />
              </el-form-item>
            </el-form>
          </div>
        </template>
      </VueDraggableNext>

      <div v-if="!promoSections.length" class="text-system-mid text-sm">
        {{ translate('No promotional sections added yet.') }}
      </div>
    </Card.Body>
  </Card.Container>
</template>
