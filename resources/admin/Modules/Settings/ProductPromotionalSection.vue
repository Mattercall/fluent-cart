<template>
  <div class="setting-wrap">
    <Card.Container>
      <Card.Header :title="$t('Promotional Section')"/>
      <Card.Body>
        <div class="fct-grid fct-grid-cols-12 gap-4">
          <div class="fct-col-span-12 md:fct-col-span-6">
            <label class="fct-form-label">{{ $t('Select Product') }}</label>
            <el-select
              v-model="selectedProductId"
              filterable
              clearable
              class="fct-w-full"
              :placeholder="$t('Choose a product')"
              @change="loadSettings"
            >
              <el-option v-for="product in products" :key="product.id" :label="product.title" :value="product.id"/>
            </el-select>
          </div>
        </div>

        <div v-if="selectedProductId" class="mt-5">
          <draggable
            v-model="sections"
            item-key="uid"
            handle=".fct-promo-section-drag"
            ghost-class="fct-promo-section-ghost"
          >
            <template #item="{ element, index }">
              <div class="fct-promo-section-card mb-4 p-4 border rounded-lg">
                <div class="fct-flex fct-items-center fct-justify-between mb-4">
                  <div class="fct-flex fct-items-center gap-2">
                    <span class="fct-promo-section-drag" style="cursor: move">⋮⋮</span>
                    <strong>{{ $t('Section') }} {{ index + 1 }}</strong>
                  </div>
                  <el-button type="danger" text @click="removeSection(index)">{{ $t('Delete') }}</el-button>
                </div>

                <div class="fct-grid fct-grid-cols-12 gap-4">
                  <div class="fct-col-span-12 md:fct-col-span-4">
                    <label class="fct-form-label">{{ $t('Promotional Image') }}</label>
                    <Attachments
                      :attachments="getImageAttachments(element)"
                      :multiple="false"
                      icon="GalleryAdd"
                      :title="$t('Add Image')"
                      @mediaUploaded="(images) => onImageSelect(index, images)"
                      @removeImage="() => removeImage(index)"
                    />
                  </div>
                  <div class="fct-col-span-12 md:fct-col-span-8">
                    <div class="mb-4">
                      <label class="fct-form-label">{{ $t('Heading') }}</label>
                      <el-input v-model="element.heading" :placeholder="$t('Add heading')" maxlength="120" show-word-limit/>
                    </div>
                    <div>
                      <label class="fct-form-label">{{ $t('Short Description') }}</label>
                      <el-input
                        v-model="element.description"
                        type="textarea"
                        :rows="5"
                        maxlength="280"
                        show-word-limit
                        :placeholder="$t('Add short description')"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </draggable>

          <el-button type="default" @click="addSection">{{ $t('Add Promotional Section') }}</el-button>

          <div class="mt-5">
            <el-button type="primary" :loading="saving" @click="saveSettings">{{ $t('Save Promotional Sections') }}</el-button>
          </div>
        </div>
      </Card.Body>
    </Card.Container>
  </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {VueDraggableNext as draggable} from 'vue-draggable-next';
import * as Card from '@/Bits/Components/Card/Card.js';
import Attachments from '@/Bits/Components/Attachment/Attachments.vue';
import Rest from '@/utils/http/Rest';
import Notify from '@/utils/Notify';

const products = ref([]);
const selectedProductId = ref(null);
const saving = ref(false);
const sections = ref([]);

const emptySection = () => ({
  uid: `section_${Date.now()}_${Math.random().toString(16).slice(2)}`,
  image: {id: 0, url: '', title: ''},
  heading: '',
  description: ''
});

const normalizeSections = (inputSections = []) => {
  return (Array.isArray(inputSections) ? inputSections : []).map((section) => ({
    uid: `section_${Date.now()}_${Math.random().toString(16).slice(2)}`,
    image: {
      id: section?.image?.id || 0,
      url: section?.image?.url || '',
      title: section?.image?.title || ''
    },
    heading: section?.heading || '',
    description: section?.description || ''
  }));
};

const getImageAttachments = (section) => {
  if (!section?.image?.url) {
    return [];
  }

  return [{
    id: section.image.id,
    url: section.image.url,
    title: section.image.title || ''
  }];
};

const loadProducts = () => {
  Rest.get('settings/promotional-section/products').then((response) => {
    products.value = response.products || [];
  });
};

const loadSettings = () => {
  if (!selectedProductId.value) {
    sections.value = [];
    return;
  }

  Rest.get('settings/promotional-section', {product_id: selectedProductId.value}).then((response) => {
    sections.value = normalizeSections(response.settings || []);
  });
};

const addSection = () => {
  sections.value.push(emptySection());
};

const removeSection = (index) => {
  sections.value.splice(index, 1);
};

const onImageSelect = (index, images) => {
  const image = images?.[0] || {id: 0, url: '', title: ''};
  sections.value[index].image = {id: image.id || 0, url: image.url || '', title: image.title || ''};
};

const removeImage = (index) => {
  sections.value[index].image = {id: 0, url: '', title: ''};
};

const saveSettings = () => {
  if (!selectedProductId.value) {
    return;
  }

  saving.value = true;

  const payloadSections = sections.value.map((section) => ({
    image: section.image,
    heading: section.heading,
    description: section.description
  }));

  Rest.post('settings/promotional-section', {
    product_id: selectedProductId.value,
    sections: payloadSections
  }).then((response) => {
    sections.value = normalizeSections(response.settings || []);
    Notify.success(response.message);
  }).finally(() => {
    saving.value = false;
  });
};

onMounted(() => {
  loadProducts();
});
</script>
