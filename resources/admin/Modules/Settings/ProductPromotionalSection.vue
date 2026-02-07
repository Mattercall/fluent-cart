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

        <div v-if="selectedProductId" class="fct-grid fct-grid-cols-12 gap-4 mt-5">
          <div class="fct-col-span-12 md:fct-col-span-4">
            <label class="fct-form-label">{{ $t('Promotional Image') }}</label>
            <Attachments
              :attachments="imageAttachments"
              :multiple="false"
              icon="GalleryAdd"
              :title="$t('Add Image')"
              @mediaUploaded="onImageSelect"
              @removeImage="removeImage"
            />
          </div>
          <div class="fct-col-span-12 md:fct-col-span-8">
            <div class="mb-4">
              <label class="fct-form-label">{{ $t('Heading') }}</label>
              <el-input v-model="form.heading" :placeholder="$t('Add heading')" maxlength="120" show-word-limit/>
            </div>
            <div>
              <label class="fct-form-label">{{ $t('Short Description') }}</label>
              <el-input
                v-model="form.description"
                type="textarea"
                :rows="5"
                maxlength="280"
                show-word-limit
                :placeholder="$t('Add short description')"
              />
            </div>
          </div>
        </div>

        <div v-if="selectedProductId" class="mt-5">
          <el-button type="primary" :loading="saving" @click="saveSettings">{{ $t('Save Promotional Section') }}</el-button>
        </div>
      </Card.Body>
    </Card.Container>
  </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue';
import * as Card from '@/Bits/Components/Card/Card.js';
import Attachments from '@/Bits/Components/Attachment/Attachments.vue';
import Rest from '@/utils/http/Rest';
import Notify from '@/utils/Notify';

const products = ref([]);
const selectedProductId = ref(null);
const saving = ref(false);
const form = ref({
  image: {id: 0, url: '', title: ''},
  heading: '',
  description: ''
});

const imageAttachments = computed(() => {
  if (!form.value.image?.url) {
    return [];
  }

  return [{
    id: form.value.image.id,
    url: form.value.image.url,
    title: form.value.image.title || ''
  }];
});

const loadProducts = () => {
  Rest.get('settings/promotional-section/products').then((response) => {
    products.value = response.products || [];
  });
};

const loadSettings = () => {
  if (!selectedProductId.value) {
    form.value = {image: {id: 0, url: '', title: ''}, heading: '', description: ''};
    return;
  }

  Rest.get('settings/promotional-section', {product_id: selectedProductId.value}).then((response) => {
    form.value = response.settings || {image: {id: 0, url: '', title: ''}, heading: '', description: ''};
  });
};

const onImageSelect = (images) => {
  const image = images?.[0] || {id: 0, url: '', title: ''};
  form.value.image = {id: image.id || 0, url: image.url || '', title: image.title || ''};
};

const removeImage = () => {
  form.value.image = {id: 0, url: '', title: ''};
};

const saveSettings = () => {
  if (!selectedProductId.value) {
    return;
  }

  saving.value = true;

  Rest.post('settings/promotional-section', {
    product_id: selectedProductId.value,
    heading: form.value.heading,
    description: form.value.description,
    image: form.value.image
  }).then((response) => {
    form.value = response.settings || form.value;
    Notify.success(response.message);
  }).finally(() => {
    saving.value = false;
  });
};

onMounted(() => {
  loadProducts();
});
</script>
