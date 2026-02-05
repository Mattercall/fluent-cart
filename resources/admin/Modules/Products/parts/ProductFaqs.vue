<script setup>
import {inject, nextTick, ref, watch} from 'vue';
import translate from "@/utils/translator/Translator";

const props = defineProps({
  product: Object,
  productEditModel: Object
});

const triggerChange = inject('triggerChange', () => {});
const faqs = ref([]);
const isSyncing = ref(false);

const normalizeFaqs = (list) => {
  if (!Array.isArray(list)) {
    return [];
  }

  return list.map((item) => ({
    question: item?.question ?? '',
    answer: item?.answer ?? ''
  }));
};

const syncFaqs = () => {
  isSyncing.value = true;
  faqs.value = normalizeFaqs(props.product?.detail?.other_info?.faqs);
  nextTick(() => {
    isSyncing.value = false;
  });
};

const addFaq = () => {
  faqs.value.push({
    question: '',
    answer: ''
  });
};

const removeFaq = (index) => {
  faqs.value.splice(index, 1);
};

watch(() => props.product?.detail?.other_info?.faqs, syncFaqs, {deep: true, immediate: true});

watch(faqs, (value) => {
  if (isSyncing.value) {
    return;
  }
  props.productEditModel.onChangeInputField('faqs', value);
  triggerChange();
}, {deep: true});
</script>

<template>
  <div class="fct-product-faqs el-form--label-top">
    <div class="fct-card-header">
      <div>
        <h3 class="mb-1">{{ translate('Product FAQs') }}</h3>
        <p class="text-sm text-system-light">
          {{ translate('Add common questions and answers that appear on the product page.') }}
        </p>
      </div>
      <el-button size="small" type="primary" @click="addFaq">
        {{ translate('Add FAQ') }}
      </el-button>
    </div>

    <div v-if="faqs.length" class="fct-card-body">
      <div v-for="(faq, index) in faqs" :key="`faq-${index}`" class="fct-product-faq-item">
        <div class="fct-card-header fct-card-header-sm">
          <strong>{{ translate('FAQ %s', index + 1) }}</strong>
          <el-button size="small" type="danger" plain @click="removeFaq(index)">
            {{ translate('Remove') }}
          </el-button>
        </div>
        <el-form-item :label="translate('Question')">
          <el-input v-model="faq.question" placeholder="">
          </el-input>
        </el-form-item>
        <el-form-item :label="translate('Answer')">
          <el-input v-model="faq.answer" type="textarea" :rows="3" placeholder="">
          </el-input>
        </el-form-item>
      </div>
    </div>

    <div v-else class="fct-empty-state">
      <p class="text-sm text-system-light">
        {{ translate('No FAQs added yet.') }}
      </p>
    </div>
  </div>
</template>
