<script setup>
import {computed, getCurrentInstance, nextTick, onMounted, ref, watch} from 'vue';
import translate from '@/utils/translator/Translator';
import Badge from '@/Bits/Components/Badge.vue';

const model = defineModel();

const props = defineProps({
  field: {
    type: Object,
    default: () => ({})
  }
});

const selfRef = getCurrentInstance().ctx;
const isActive = ref('no');
const productOptions = ref([]);
const loadingProducts = ref(false);
const appReady = ref(false);

const rows = computed(() => model.value?.products || []);

const ensureModel = () => {
  if (!model.value || typeof model.value !== 'object') {
    model.value = {
      active: 'no',
      products: []
    };
  }

  if (!Array.isArray(model.value.products)) {
    model.value.products = [];
  }
};

const createImage = () => ({ url: '', description: '' });

const createRow = () => ({
  product_id: '',
  video_url: '',
  images: [createImage()]
});

const updateActive = (value) => {
  ensureModel();
  nextTick(() => {
    model.value.active = value;
  });
};

const addRow = () => {
  ensureModel();
  model.value.products.push(createRow());
};

const removeRow = (index) => {
  ensureModel();
  model.value.products.splice(index, 1);
};

const addImage = (rowIndex) => {
  ensureModel();
  const current = model.value.products[rowIndex];
  if (!current.images || !Array.isArray(current.images)) {
    current.images = [];
  }
  current.images.push(createImage());
};

const removeImage = (rowIndex, imageIndex) => {
  ensureModel();
  const current = model.value.products[rowIndex];
  if (!current.images || !Array.isArray(current.images)) {
    return;
  }

  current.images.splice(imageIndex, 1);
};

const searchProducts = (query = '') => {
  loadingProducts.value = true;

  selfRef
      .$get('products/searchProductByName', {
        name: query
      })
      .then((response) => {
        const products = response.products || [];
        productOptions.value = products.map((product) => ({
          label: product.post_title,
          value: product.ID
        }));
      })
      .finally(() => {
        loadingProducts.value = false;
      });
};

onMounted(() => {
  ensureModel();
  isActive.value = model.value.active || 'no';
  searchProducts('');
  appReady.value = true;
});

watch(() => model.value, (newVal) => {
  ensureModel();
  if (newVal) {
    isActive.value = newVal.active || 'no';
  }
}, {deep: true});
</script>

<template>
  <div v-if="appReady" class="fct-content-card-list-item py-4 px-6">
    <div class="fct-content-card-list-head">
      <div class="flex items-start gap-2 flex-row">
        <h4 class="mb-0">{{ field.title }}</h4>
        <Badge size="small" :type="isActive === 'yes' ? 'active':'inactive'" :hide-icon="true">
          {{ isActive === 'yes' ? translate('Active') : translate('Inactive') }}
        </Badge>
      </div>
    </div>

    <div class="fct-content-card-list-content" v-if="field.description">
      <p>{{ field.description }}</p>
    </div>

    <div class="fct-content-card-list-action">
      <div class="pr-4">
        <el-switch active-value="yes" inactive-value="no" v-model="isActive" @change="updateActive"/>
      </div>
    </div>

    <div v-if="isActive === 'yes'" class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
      <div class="flex justify-between items-center mb-4">
        <h5 class="text-base font-medium mb-0">{{ translate('Per-product Sale Booster content') }}</h5>
        <el-button type="primary" @click="addRow">{{ translate('Add Product') }}</el-button>
      </div>

      <div v-if="!rows.length" class="text-sm text-gray-500">
        {{ translate('No product configured yet.') }}
      </div>

      <div v-for="(row, rowIndex) in rows" :key="rowIndex" class="border rounded-lg p-4 mb-4 bg-gray-50 dark:bg-gray-800">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">{{ translate('Product') }}</label>
            <el-select
                v-model="row.product_id"
                filterable
                remote
                clearable
                :remote-method="searchProducts"
                :loading="loadingProducts"
                class="w-full"
                :placeholder="translate('Search product by name')"
            >
              <el-option
                  v-for="item in productOptions"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
              />
            </el-select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">{{ translate('Video URL') }}</label>
            <el-input v-model="row.video_url" :placeholder="translate('https://www.youtube.com/... or .mp4 URL')"/>
          </div>
        </div>

        <div class="mt-4">
          <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium">{{ translate('Image URLs + description') }}</label>
            <el-button size="small" @click="addImage(rowIndex)">{{ translate('Add Image') }}</el-button>
          </div>

          <div v-for="(image, imageIndex) in row.images" :key="`${rowIndex}-${imageIndex}`" class="grid grid-cols-1 md:grid-cols-[2fr_1fr_auto] gap-2 mb-2">
            <el-input v-model="image.url" :placeholder="translate('Image URL')"/>
            <el-input v-model="image.description" :placeholder="translate('Short description')"/>
            <el-button type="danger" plain @click="removeImage(rowIndex, imageIndex)">{{ translate('Remove') }}</el-button>
          </div>
        </div>

        <div class="mt-3 text-right">
          <el-button type="danger" plain @click="removeRow(rowIndex)">{{ translate('Remove Product') }}</el-button>
        </div>
      </div>
    </div>
  </div>
</template>
