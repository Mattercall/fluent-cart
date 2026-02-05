<script setup>
import * as Card from '@/Bits/Components/Card/Card.js';
import {VueDraggableNext} from 'vue-draggable-next';
import {computed, ref, watch} from 'vue';
import translate from "@/utils/translator/Translator";
import MediaInput from "@/Bits/Components/Inputs/MediaInput.vue";
import DynamicIcon from "@/Bits/Components/Icons/DynamicIcon.vue";

const props = defineProps({
  product: Object,
  productEditModel: Object
});

const maxSections = 5;
const sections = ref([]);

const canAddMore = computed(() => sections.value.length < maxSections);

const ensureSections = () => {
  if (!props.product.detail) {
    props.product.detail = {};
  }

  if (!props.product.detail.other_info) {
    props.product.detail.other_info = {};
  }

  if (!Array.isArray(props.product.detail.other_info.promotional_sections)) {
    props.product.detail.other_info.promotional_sections = [];
  }
};

const syncSections = (nextSections) => {
  ensureSections();
  props.product.detail.other_info.promotional_sections = nextSections;
  props.productEditModel.onChangeInputField('promotional_sections', nextSections);
};

const addSection = () => {
  if (!canAddMore.value) {
    return;
  }
  const nextSections = [
    ...sections.value,
    {
      key: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
      heading: '',
      description: '',
      image: [],
      badge: ''
    }
  ];

  sections.value = nextSections;
  syncSections(nextSections);
};

const removeSection = (index) => {
  const nextSections = sections.value.filter((section, idx) => idx !== index);
  sections.value = nextSections;
  syncSections(nextSections);
};

const updateSection = (index, field, value) => {
  const nextSections = sections.value.map((section, idx) => {
    if (idx !== index) {
      return section;
    }
    return {
      ...section,
      [field]: value
    };
  });

  sections.value = nextSections;
  syncSections(nextSections);
};

const handleReorder = () => {
  syncSections([...sections.value]);
};

watch(() => props.product, () => {
  if (!props.product) {
    return;
  }
  ensureSections();
  sections.value = props.product.detail.other_info.promotional_sections.map((section, index) => ({
    key: section.key || `${Date.now()}-${index}`,
    heading: section.heading || '',
    description: section.description || '',
    image: section.image || [],
    badge: section.badge || ''
  }));
}, {immediate: true});
</script>

<template>
  <Card.Container>
    <Card.Header :title="translate('Promotional Sections')" border_bottom>
      <template #action>
        <el-button type="primary" :disabled="!canAddMore" @click="addSection">
          <DynamicIcon name="Plus"/>
          {{ translate('Add Section') }}
        </el-button>
      </template>
    </Card.Header>
    <Card.Body>
      <p class="text-sm text-gray-500 mb-4">
        {{
          translate(
            'Add up to %1$s promotional sections to highlight key benefits.',
            maxSections
          )
        }}
      </p>

      <VueDraggableNext
        :list="sections"
        item-key="key"
        handle=".fct-drag-handle"
        @end="handleReorder"
      >
        <div v-for="(section, index) in sections" :key="section.key" class="fct-admin-input-wrapper mb-6">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <span class="fct-drag-handle cursor-move text-gray-400">
                <DynamicIcon name="ReorderDotsVertical"/>
              </span>
              <h4 class="text-base font-medium">
                {{ translate('Section') }} {{ index + 1 }}
              </h4>
            </div>
            <el-button type="text" class="text-red-500" @click="removeSection(index)">
              <DynamicIcon name="Delete"/>
              {{ translate('Remove') }}
            </el-button>
          </div>

          <el-form label-position="top" require-asterisk-position="right">
            <el-form-item :label="translate('Title')">
              <el-input
                v-model="section.heading"
                type="text"
                :placeholder="translate('Section title')"
                @input="value => updateSection(index, 'heading', value)"
              />
            </el-form-item>

            <el-form-item :label="translate('Description')">
              <el-input
                v-model="section.description"
                type="textarea"
                :rows="4"
                :placeholder="translate('Section description')"
                @input="value => updateSection(index, 'description', value)"
              />
            </el-form-item>

            <el-form-item :label="translate('Badge / Number')">
              <el-input
                v-model="section.badge"
                type="text"
                :placeholder="translate('Optional badge (auto if left blank)')"
                @input="value => updateSection(index, 'badge', value)"
              />
            </el-form-item>

            <el-form-item :label="translate('Image')">
              <MediaInput
                v-model="section.image"
                icon="Upload"
                :title="translate('Upload Image')"
                @update:modelValue="value => updateSection(index, 'image', value)"
              />
            </el-form-item>
          </el-form>
        </div>
      </VueDraggableNext>

      <el-empty
        v-if="sections.length === 0"
        :description="translate('No promotional sections added yet.')"
      />
    </Card.Body>
  </Card.Container>
</template>
