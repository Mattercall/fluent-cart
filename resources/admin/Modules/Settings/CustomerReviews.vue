<template>
  <div class="setting-wrap">
    <CardContainer>
      <CardHeader :title="$t('Customer Reviews')">
        <template #action>
          <div class="fct-btn-group">
            <el-button @click="exportJson">{{ $t('Export JSON') }}</el-button>
            <el-button @click="openImport">{{ $t('Import JSON') }}</el-button>
            <el-button type="primary" @click="openEditor()">{{ $t('Add Review') }}</el-button>
          </div>
        </template>
      </CardHeader>
      <CardBody>
        <div class="fct-grid fct-grid-cols-12 gap-3 mb-4">
          <div class="fct-col-span-12 md:fct-col-span-4">
            <el-input v-model="filters.search" :placeholder="$t('Search review, reviewer...')" clearable @input="loadReviews"/>
          </div>
          <div class="fct-col-span-12 md:fct-col-span-4">
            <el-select v-model="filters.product_id" clearable filterable :placeholder="$t('Filter by product')" @change="loadReviews">
              <el-option v-for="p in products" :key="p.id" :label="p.title" :value="p.id" />
            </el-select>
          </div>
          <div class="fct-col-span-12 md:fct-col-span-4 text-right" v-if="selectedIds.length">
            <el-button type="danger" @click="bulkDelete">{{ $t('Bulk Delete') }} ({{ selectedIds.length }})</el-button>
          </div>
        </div>

        <el-table :data="reviews" @selection-change="onSelectionChange" v-loading="loading">
          <el-table-column type="selection" width="40"/>
          <el-table-column :label="$t('Reviewer')" min-width="180">
            <template #default="scope">
              <strong>{{ scope.row.reviewer_name }}</strong>
              <div class="text-muted">{{ scope.row.country_flag }} {{ scope.row.country }}</div>
            </template>
          </el-table-column>
          <el-table-column :label="$t('Product')" min-width="220">
            <template #default="scope">
              {{ productMap[scope.row.product_id] || ('#' + scope.row.product_id) }}
            </template>
          </el-table-column>
          <el-table-column :label="$t('Rating')" width="90">
            <template #default="scope">{{ scope.row.rating }}/5</template>
          </el-table-column>
          <el-table-column :label="$t('Enabled')" width="110">
            <template #default="scope">
              <el-switch v-model="scope.row.enabledBool" @change="toggleEnabled(scope.row)"/>
            </template>
          </el-table-column>
          <el-table-column :label="$t('Actions')" width="140">
            <template #default="scope">
              <el-button link type="primary" @click="openEditor(scope.row)">{{ $t('Edit') }}</el-button>
              <el-button link type="danger" @click="deleteReview(scope.row.id)">{{ $t('Delete') }}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </CardBody>
    </CardContainer>

    <el-dialog v-model="isEditorOpen" :title="form.id ? $t('Edit Review') : $t('Add Review')" width="760px">
      <div class="fct-grid fct-grid-cols-12 gap-3">
        <div class="fct-col-span-12 md:fct-col-span-6"><el-input v-model="form.reviewer_name" :placeholder="$t('Reviewer name')"/></div>
        <div class="fct-col-span-12 md:fct-col-span-4"><el-input v-model="form.country" :placeholder="$t('Country')"/></div>
        <div class="fct-col-span-12 md:fct-col-span-2"><el-input v-model="form.country_flag" :placeholder="$t('Flag')"/></div>
        <div class="fct-col-span-12 md:fct-col-span-4">
          <el-select v-model="form.product_id" filterable :placeholder="$t('Select product')">
            <el-option v-for="p in products" :key="p.id" :label="p.title" :value="p.id"/>
          </el-select>
        </div>
        <div class="fct-col-span-12 md:fct-col-span-4"><el-input v-model="form.product_slug" :placeholder="$t('Product slug (optional)')"/></div>
        <div class="fct-col-span-12 md:fct-col-span-2"><el-input-number v-model="form.rating" :min="1" :max="5"/></div>
        <div class="fct-col-span-12 md:fct-col-span-2"><el-input-number v-model="form.sort_order" :min="0"/></div>
        <div class="fct-col-span-12 md:fct-col-span-4"><el-date-picker v-model="form.review_time" type="datetime" value-format="YYYY-MM-DD HH:mm:ss"/></div>
        <div class="fct-col-span-12"><el-input type="textarea" :rows="5" v-model="form.review_text" :placeholder="$t('Review text')"/></div>
        <div class="fct-col-span-12"><el-switch v-model="form.enabledBool"/> {{ $t('Enabled') }}</div>
      </div>
      <template #footer>
        <el-button @click="isEditorOpen = false">{{ $t('Cancel') }}</el-button>
        <el-button type="primary" @click="saveReview">{{ $t('Save') }}</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="isImportOpen" :title="$t('Import Reviews JSON')" width="680px">
      <el-input type="textarea" :rows="12" v-model="importText"/>
      <template #footer>
        <el-button @click="isImportOpen = false">{{ $t('Cancel') }}</el-button>
        <el-button type="primary" @click="importJson">{{ $t('Import') }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue';
import * as Card from '@/Bits/Components/Card/Card.js';
import Rest from '@/utils/http/Rest';

const CardContainer = Card.Container;
const CardHeader = Card.Header;
const CardBody = Card.Body;

const loading = ref(false);
const reviews = ref([]);
const products = ref([]);
const selectedIds = ref([]);
const isEditorOpen = ref(false);
const isImportOpen = ref(false);
const importText = ref('');

const filters = ref({search: '', product_id: null});

const defaultForm = () => ({
  id: null,
  reviewer_name: '',
  country: '',
  country_flag: '🇺🇸',
  rating: 5,
  review_text: '',
  review_time: new Date().toISOString().slice(0, 19).replace('T', ' '),
  product_id: null,
  product_slug: '',
  enabled: 'yes',
  enabledBool: true,
  sort_order: 0
});
const form = ref(defaultForm());

const productMap = computed(() => Object.fromEntries(products.value.map(p => [p.id, p.title])));

const loadProducts = () => Rest.get('settings/customer-reviews/products', {}).then(r => products.value = r.products || []);

const loadReviews = () => {
  loading.value = true;
  return Rest.get('settings/customer-reviews', filters.value)
    .then((r) => {
      reviews.value = (r.reviews || []).map(item => ({...item, enabledBool: item.enabled !== 'no'}));
    })
    .finally(() => loading.value = false);
};

const openEditor = (item = null) => {
  form.value = item ? {...item, enabledBool: item.enabled !== 'no'} : defaultForm();
  isEditorOpen.value = true;
};

const saveReview = () => {
  const payload = {...form.value, enabled: form.value.enabledBool ? 'yes' : 'no'};
  const request = payload.id ? Rest.post(`settings/customer-reviews/${payload.id}`, payload) : Rest.post('settings/customer-reviews', payload);
  request.then(() => {
    isEditorOpen.value = false;
    loadReviews();
  });
};

const deleteReview = (id) => Rest.delete(`settings/customer-reviews/${id}`).then(loadReviews);
const bulkDelete = () => Rest.post('settings/customer-reviews/bulk-delete', {ids: selectedIds.value}).then(loadReviews);
const onSelectionChange = (rows) => selectedIds.value = rows.map(r => r.id);
const toggleEnabled = (row) => Rest.post(`settings/customer-reviews/${row.id}`, {...row, enabled: row.enabledBool ? 'yes' : 'no'}).then(loadReviews);

const exportJson = () => {
  Rest.get('settings/customer-reviews/export', {}).then((r) => {
    const text = JSON.stringify(r, null, 2);
    navigator.clipboard?.writeText(text);
  });
};

const openImport = () => {
  importText.value = '';
  isImportOpen.value = true;
};

const importJson = () => {
  let parsed = {};
  try {
    parsed = JSON.parse(importText.value || '{}');
  } catch (e) {
    return;
  }
  Rest.post('settings/customer-reviews/import', parsed).then(() => {
    isImportOpen.value = false;
    loadReviews();
  });
};

onMounted(() => {
  loadProducts();
  loadReviews();
});
</script>
