<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const select = ref(null);

onMounted(() => {
    const $select = window.jQuery(select.value);

    $select
        .select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: props.placeholder,
        })
        .val(props.modelValue)
        .trigger('change.select2')
        .on('select2:select', (event) => emit('update:modelValue', event.target.value));
});

watch(() => props.modelValue, (value) => {
    window.jQuery(select.value).val(value).trigger('change.select2');
});

onBeforeUnmount(() => {
    window.jQuery(select.value).off().select2('destroy');
});
</script>

<template>
    <select ref="select" class="form-control">
        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
</template>
