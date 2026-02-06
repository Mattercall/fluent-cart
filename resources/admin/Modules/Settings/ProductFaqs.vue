<template>
  <div class="setting-wrap">
    <CardContainer>
      <CardHeader :title="$t('FAQs')">
        <template #action>
          <el-button type="primary" @click="openEditor()">{{ $t('Add FAQ') }}</el-button>
        </template>
      </CardHeader>
      <CardBody>
        <div class="fct-grid fct-grid-cols-12 gap-3 mb-4">
          <div class="fct-col-span-12 md:fct-col-span-6">
            <el-input v-model="filters.search" :placeholder="$t('Search question...')" clearable @input="loadFaqs"/>
          </div>
          <div class="fct-col-span-12 md:fct-col-span-6">
            <el-select v-model="filters.product_id" clearable filterable :placeholder="$t('Filter by product')" @change="loadFaqs">
              <el-option v-for="p in products" :key="p.id" :label="p.title" :value="p.id" />
            </el-select>
          </div>
        </div>

        <el-table :data="faqs" v-loading="loading">
          <el-table-column :label="$t('Question')" min-width="260" prop="question"/>
          <el-table-column :label="$t('Product')" min-width="200">
            <template #default="scope">
              <span v-if="scope.row.is_global === 'yes'">{{ $t('Global FAQ') }}</span>
              <span v-else>{{ productMap[scope.row.product_id] || ('#' + scope.row.product_id) }}</span>
            </template>
          </el-table-column>
          <el-table-column :label="$t('Order')" width="90" prop="sort_order"/>
          <el-table-column :label="$t('Enabled')" width="110">
            <template #default="scope">
              <el-switch v-model="scope.row.enabledBool" @change="toggleEnabled(scope.row)"/>
            </template>
          </el-table-column>
          <el-table-column :label="$t('Actions')" width="140">
            <template #default="scope">
              <el-button link type="primary" @click="openEditor(scope.row)">{{ $t('Edit') }}</el-button>
              <el-button link type="danger" @click="deleteFaq(scope.row.id)">{{ $t('Delete') }}</el-button>
            </template>
          </el-table-column>
        </el-table>
      </CardBody>
    </CardContainer>

    <el-dialog v-model="isEditorOpen" :title="form.id ? $t('Edit FAQ') : $t('Add FAQ')" width="860px">
      <div class="fct-grid fct-grid-cols-12 gap-3">
        <div class="fct-col-span-12">
          <el-input v-model="form.question" :placeholder="$t('Question')"/>
        </div>

        <div class="fct-col-span-12 md:fct-col-span-6">
          <el-switch v-model="form.is_global_bool"/>
          <span class="ml-2">{{ $t('Global FAQ') }}</span>
        </div>

        <div class="fct-col-span-12 md:fct-col-span-6">
          <el-switch v-model="form.enabledBool"/>
          <span class="ml-2">{{ $t('Enabled') }}</span>
        </div>

        <div class="fct-col-span-12 md:fct-col-span-6" v-if="!form.is_global_bool">
          <el-select v-model="form.product_id" filterable clearable :placeholder="$t('Select product')">
            <el-option v-for="p in products" :key="p.id" :label="p.title" :value="p.id"/>
          </el-select>
        </div>

        <div class="fct-col-span-12 md:fct-col-span-4" v-if="!form.is_global_bool">
          <el-input v-model="form.product_slug" :placeholder="$t('Product slug (optional)')"/>
        </div>

        <div class="fct-col-span-12 md:fct-col-span-2">
          <el-input-number v-model="form.sort_order" :min="0"/>
        </div>

        <div class="fct-col-span-12">
          <div class="custom-wp-editor-wrapper">
            <WpEditor v-model="form.answer" :height="220"/>
          </div>
        </div>
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
import WpEditor from '@/Bits/Components/Inputs/WpEditor.vue';

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
  sort_order: 0,
  enabled: 'yes',
  enabledBool: true,
  is_global: 'no',
  is_global_bool: false
});

const form = ref(defaultForm());
const productMap = computed(() => Object.fromEntries(products.value.map(p => [p.id, p.title])));

const loadProducts = () => Rest.get('settings/product-faqs/products', {}).then(r => products.value = r.products || []);

const loadFaqs = () => {
  loading.value = true;
  return Rest.get('settings/product-faqs', filters.value)
    .then((r) => {
      faqs.value = (r.faqs || []).map(item => ({
        ...item,
        enabledBool: item.enabled !== 'no',
        is_global_bool: item.is_global === 'yes'
      }));
    })
    .finally(() => loading.value = false);
};

const openEditor = (item = null) => {
  form.value = item ? {
    ...item,
    enabledBool: item.enabled !== 'no',
    is_global_bool: item.is_global === 'yes'
  } : defaultForm();
  isEditorOpen.value = true;
};

const saveFaq = () => {
  const payload = {
    ...form.value,
    enabled: form.value.enabledBool ? 'yes' : 'no',
    is_global: form.value.is_global_bool ? 'yes' : 'no'
  };

  const request = payload.id ? Rest.post(`settings/product-faqs/${payload.id}`, payload) : Rest.post('settings/product-faqs', payload);

  request.then(() => {
    isEditorOpen.value = false;
    loadFaqs();
  });
};

const deleteFaq = (id) => Rest.delete(`settings/product-faqs/${id}`).then(loadFaqs);
const toggleEnabled = (row) => Rest.post(`settings/product-faqs/${row.id}`, {
  ...row,
  enabled: row.enabledBool ? 'yes' : 'no',
  is_global: row.is_global_bool ? 'yes' : 'no'
}).then(loadFaqs);

onMounted(() => {
  loadProducts();
  loadFaqs();
});
</script>
