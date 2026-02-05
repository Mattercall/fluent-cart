<script setup>
import {computed, nextTick, ref, watch, inject} from 'vue';
import {VueDraggableNext} from 'vue-draggable-next';
import DynamicIcon from "@/Bits/Components/Icons/DynamicIcon.vue";
import translate from "@/utils/translator/Translator";

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  productEditModel: Object
});

const emit = defineEmits(['update:modelValue']);

const triggerChange = inject('triggerChange');

const faqs = ref([]);
const isSyncing = ref(false);

const hasFaqs = computed(() => faqs.value.length > 0);

const buildFaqs = (items = []) => {
  if (!Array.isArray(items)) {
    return [];
  }

  return items.map((item, index) => ({
    id: item?.id || `faq-${Date.now()}-${index}`,
    question: item?.question || '',
    answer: item?.answer || ''
  }));
};

const serializeFaqs = () => {
  return faqs.value
      .map(({question, answer}) => ({
        question: question?.trim() || '',
        answer: answer || ''
      }));
};

const syncFaqs = () => {
  const sanitizedFaqs = serializeFaqs();

  emit('update:modelValue', sanitizedFaqs);

  if (props.productEditModel) {
    props.productEditModel.onChangeInputField('faqs', sanitizedFaqs);
    triggerChange?.();
  }
};

const addFaq = () => {
  faqs.value.push({
    id: `faq-${Date.now()}-${faqs.value.length}`,
    question: '',
    answer: ''
  });
};

const removeFaq = (index) => {
  faqs.value.splice(index, 1);
};

watch(
    () => props.modelValue,
    async (value) => {
      isSyncing.value = true;
      faqs.value = buildFaqs(value);
      await nextTick();
      isSyncing.value = false;
    },
    {immediate: true}
);

watch(
    faqs,
    () => {
      if (isSyncing.value) {
        return;
      }
      syncFaqs();
    },
    {deep: true}
);
</script>

<template>
  <div class="fct-admin-summary-item fct-product-faqs-admin">
    <div class="fct-product-faqs-admin-header">
      <div>
        <h4 class="fct-admin-summary-item-title">
          {{ translate('FAQs') }}
        </h4>
        <p class="fct-product-faqs-admin-help">
          {{ translate('Add answers to the most common product questions.') }}
        </p>
      </div>
      <el-button type="primary" size="small" @click="addFaq">
        {{ translate('Add FAQ') }}
      </el-button>
    </div>

    <div v-if="hasFaqs" class="fct-product-faqs-admin-list">
      <VueDraggableNext v-model="faqs" handle=".fct-faq-drag-handle" class="fct-product-faqs-admin-draggable">
        <div v-for="(faq, index) in faqs" :key="faq.id" class="fct-product-faqs-admin-item">
          <div class="fct-product-faqs-admin-item-header">
            <button type="button" class="fct-faq-drag-handle" aria-label="Drag to reorder FAQ">
              <DynamicIcon name="ReorderDotsVertical"/>
            </button>
            <span class="fct-product-faqs-admin-item-title">
              {{ translate('FAQ %s', index + 1) }}
            </span>
            <el-button type="danger" text @click="removeFaq(index)">
              <DynamicIcon name="Delete"/>
              {{ translate('Remove') }}
            </el-button>
          </div>

          <div class="fct-product-faqs-admin-fields">
            <el-form-item :label="translate('Question')">
              <el-input v-model="faq.question" :placeholder="translate('Enter the question')" />
            </el-form-item>
            <el-form-item :label="translate('Answer')">
              <el-input
                  v-model="faq.answer"
                  type="textarea"
                  :rows="3"
                  :placeholder="translate('Write a clear, concise answer')"
              />
            </el-form-item>
          </div>
        </div>
      </VueDraggableNext>
    </div>

    <div v-else class="fct-product-faqs-admin-empty">
      <p>{{ translate('No FAQs yet. Add one to help customers with common questions.') }}</p>
    </div>
  </div>
</template>
