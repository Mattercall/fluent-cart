<template>
  <div class="setting-wrap">
    <CardContainer>
      <CardHeader :title="$t('Portfolio')">
        <template #action>
          <el-button type="primary" @click="openEditor()">{{ $t('Add Portfolio') }}</el-button>
        </template>
      </CardHeader>
      <CardBody>
        <div class="fct-grid fct-grid-cols-12 gap-3 mb-4">
          <div class="fct-col-span-12 md:fct-col-span-6">
            <el-input v-model="filters.search" :placeholder="$t('Search portfolio...')" clearable @input="loadEntries"/>
          </div>
          <div class="fct-col-span-12 md:fct-col-span-6">
            <el-select v-model="filters.product_id" clearable filterable :placeholder="$t('Filter by product')" @change="loadEntries">
              <el-option v-for="p in products" :key="p.id" :label="p.title" :value="p.id"/>
            </el-select>
          </div>
        </div>

        <el-table :data="entries" v-loading="loading">
          <el-table-column :label="$t('Title')" min-width="220" prop="title"/>
          <el-table-column :label="$t('Product')" min-width="220">
            <template #default="scope">{{ productMap[scope.row.product_id] || ('#' + scope.row.product_id) }}</template>
          </el-table-column>
          <el-table-column :label="$t('Price Range')" width="130" prop="price_range"/>
          <el-table-column :label="$t('Date')" width="130" prop="date"/>
          <el-table-column :label="$t('Enabled')" width="110">
            <template #default="scope"><el-switch v-model="scope.row.enabledBool" @change="toggleEnabled(scope.row)"/></template>
          </el-table-column>
          <el-table-column :label="$t('Actions')" width="150">
            <template #default="scope">
              <el-button link type="primary" @click="openEditor(scope.row)">{{ $t('Edit') }}</el-button>
              <el-button link type="danger" @click="deleteEntry(scope.row.id)">{{ $t('Delete') }}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </CardBody>
    </CardContainer>

    <el-dialog v-model="isEditorOpen" :title="form.id ? $t('Edit Portfolio') : $t('Add Portfolio')" width="840px">
      <div class="fct-grid fct-grid-cols-12 gap-3">
        <div class="fct-col-span-12 md:fct-col-span-8"><el-input v-model="form.title" :placeholder="$t('Portfolio Title')"/></div>
        <div class="fct-col-span-12 md:fct-col-span-4"><el-input-number v-model="form.sort_order" :min="0"/></div>
        <div class="fct-col-span-12 md:fct-col-span-6">
          <el-select v-model="form.product_id" filterable :placeholder="$t('Select product')">
            <el-option v-for="p in products" :key="p.id" :label="p.title" :value="p.id"/>
          </el-select>
        </div>
        <div class="fct-col-span-12 md:fct-col-span-6"><el-input v-model="form.product_slug" :placeholder="$t('Product slug (optional)')"/></div>
        <div class="fct-col-span-12"><el-input v-model="form.image_url" :placeholder="$t('Image URL')"/></div>
        <div class="fct-col-span-12 md:fct-col-span-6"><el-input v-model="form.price_range" :placeholder="$t('Price Range')"/></div>
        <div class="fct-col-span-12 md:fct-col-span-6"><el-date-picker v-model="form.date" type="date" value-format="YYYY-MM-DD"/></div>
        <div class="fct-col-span-12"><el-input type="textarea" :rows="3" v-model="form.small_description" :placeholder="$t('Small Description')"/></div>
        <div class="fct-col-span-12"><el-input type="textarea" :rows="6" v-model="form.full_description" :placeholder="$t('Full Description')"/></div>
        <div class="fct-col-span-12"><el-switch v-model="form.enabledBool"/> {{ $t('Enabled') }}</div>
      </div>
      <template #footer>
        <el-button @click="isEditorOpen = false">{{ $t('Cancel') }}</el-button>
        <el-button type="primary" @click="saveEntry">{{ $t('Save') }}</el-button>
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
const entries = ref([]);
const products = ref([]);
const isEditorOpen = ref(false);
const filters = ref({search: '', product_id: null});

const defaultForm = () => ({
  id: null,
  title: '',
  image_url: '',
  small_description: '',
  full_description: '',
  price_range: '',
  date: '',
  product_id: null,
  product_slug: '',
  enabled: 'yes',
  enabledBool: true,
  sort_order: 0
});

const form = ref(defaultForm());

const productMap = computed(() => Object.fromEntries(products.value.map(p => [p.id, p.title])));

const loadProducts = () => Rest.get('settings/product-portfolio/products', {}).then(r => products.value = r.products || []);

const loadEntries = () => {
  loading.value = true;
  return Rest.get('settings/product-portfolio', filters.value)
    .then((r) => entries.value = (r.entries || []).map(item => ({...item, enabledBool: item.enabled !== 'no'})))
    .finally(() => loading.value = false);
};

const openEditor = (item = null) => {
  form.value = item ? {...item, enabledBool: item.enabled !== 'no'} : defaultForm();
  isEditorOpen.value = true;
};

const saveEntry = () => {
  const payload = {...form.value, enabled: form.value.enabledBool ? 'yes' : 'no'};
  const request = payload.id ? Rest.post(`settings/product-portfolio/${payload.id}`, payload) : Rest.post('settings/product-portfolio', payload);
  request.then(() => {
    isEditorOpen.value = false;
    loadEntries();
  });
};

const deleteEntry = (id) => Rest.delete(`settings/product-portfolio/${id}`).then(loadEntries);
const toggleEnabled = (row) => Rest.post(`settings/product-portfolio/${row.id}`, {...row, enabled: row.enabledBool ? 'yes' : 'no'}).then(loadEntries);

onMounted(() => {
  loadProducts();
  loadEntries();
});
</script>
