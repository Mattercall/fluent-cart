<template>
  <div class="setting-wrap">
    <CardContainer>
      <CardHeader :title="$t('Product FAQs')">
        <template #action>
          <el-button type="primary" @click="openEditor()">{{ $t('Add FAQ') }}</el-button>
        </template>
      </CardHeader>
      <CardBody>
        <div class="fct-grid fct-grid-cols-12 gap-3 mb-4">
          <div class="fct-col-span-12 md:fct-col-span-6">
            <el-input v-model="filters.search" :placeholder="$t('Search question or answer...')" clearable @input="loadFaqs"/>
          </div>
          <div class="fct-col-span-12 md:fct-col-span-6">
            <el-select v-model="filters.product_id" clearable filterable :placeholder="$t('Filter by product')" @change="loadFaqs">
              <el-option v-for="p in products" :key="p.id" :label="p.title" :value="p.id"/>
            </el-select>
          </div>
        </div>

        <el-table :data="faqs" v-loading="loading">
          <el-table-column :label="$t('Question')" min-width="300" prop="question"/>
          <el-table-column :label="$t('Product')" min-width="220">
            <template #default="scope">
              {{ productMap[scope.row.product_id] || ('#' + scope.row.product_id) }}
            </template>
          </el-table-column>
          <el-table-column :label="$t('Order')" width="90" prop="sort_order"/>
          <el-table-column :label="$t('Enabled')" width="110">
            <template #default="scope">
              <el-switch v-model="scope.row.enabledBool" @change="toggleEnabled(scope.row)"/>
            </template>
          </el-table-column>
          <el-table-column :label="$t('Actions')" width="150">
            <template #default="scope">
              <el-button link type="primary" @click="openEditor(scope.row)">{{ $t('Edit') }}</el-button>
              <el-button link type="danger" @click="deleteFaq(scope.row.id)">{{ $t('Delete') }}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </CardBody>
    </CardContainer>

    <el-dialog v-model="isEditorOpen" :title="form.id ? $t('Edit FAQ') : $t('Add FAQ')" width="760px">
      <div class="fct-grid fct-grid-cols-12 gap-3">
        <div class="fct-col-span-12 md:fct-col-span-8"><el-input v-model="form.question" :placeholder="$t('Question')"/></div>
        <div class="fct-col-span-12 md:fct-col-span-4"><el-input-number v-model="form.sort_order" :min="0"/></div>
        <div class="fct-col-span-12 md:fct-col-span-6">
          <el-select v-model="form.product_id" filterable :placeholder="$t('Select product')">
            <el-option v-for="p in products" :key="p.id" :label="p.title" :value="p.id"/>
          </el-select>
        </div>
        <div class="fct-col-span-12 md:fct-col-span-6"><el-input v-model="form.product_slug" :placeholder="$t('Product slug (optional)')"/></div>
        <div class="fct-col-span-12"><el-input type="textarea" :rows="6" v-model="form.answer" :placeholder="$t('Answer')"/></div>
        <div class="fct-col-span-12"><el-switch v-model="form.enabledBool"/> {{ $t('Enabled') }}</div>
      </div>
      <template #footer>
        <el-button @click="isEditorOpen = false">{{ $t('Cancel') }}</el-button>
        <el-button type="primary" @click="saveFaq">{{ $t('Save') }}</el-button>
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
const faqs = ref([]);
const products = ref([]);
const isEditorOpen = ref(false);
const filters = ref({search: '', product_id: null});

const defaultForm = () => ({
  id: null,
  question: '',
  answer: '',
  product_id: null,
  product_slug: '',
  enabled: 'yes',
  enabledBool: true,
  sort_order: 0
});

const form = ref(defaultForm());

const productMap = computed(() => Object.fromEntries(products.value.map(p => [p.id, p.title])));

const loadProducts = () => Rest.get('settings/product-faqs/products', {}).then(r => products.value = r.products || []);

const loadFaqs = () => {
  loading.value = true;
  return Rest.get('settings/product-faqs', filters.value)
    .then((r) => {
      faqs.value = (r.faqs || []).map(item => ({...item, enabledBool: item.enabled !== 'no'}));
    })
    .finally(() => loading.value = false);
};

const openEditor = (item = null) => {
  form.value = item ? {...item, enabledBool: item.enabled !== 'no'} : defaultForm();
  isEditorOpen.value = true;
};

const saveFaq = () => {
  const payload = {...form.value, enabled: form.value.enabledBool ? 'yes' : 'no'};
  const request = payload.id ? Rest.post(`settings/product-faqs/${payload.id}`, payload) : Rest.post('settings/product-faqs', payload);

  request.then(() => {
    isEditorOpen.value = false;
    loadFaqs();
  });
};

const deleteFaq = (id) => Rest.delete(`settings/product-faqs/${id}`).then(loadFaqs);
const toggleEnabled = (row) => Rest.post(`settings/product-faqs/${row.id}`, {...row, enabled: row.enabledBool ? 'yes' : 'no'}).then(loadFaqs);

onMounted(() => {
  loadProducts();
  loadFaqs();
});
</script>
