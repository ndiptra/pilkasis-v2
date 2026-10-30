<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    height: {
        type: Number,
        default: 160,
    },
    placeholder: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const editor = ref(null);

onMounted(() => {
    const $editor = window.jQuery(editor.value);

    $editor.summernote({
        height: props.height,
        placeholder: props.placeholder,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['hr']],
            ['misc', ['undo', 'redo', 'codeview']],
        ],
        callbacks: {
            onChange: (contents) => emit('update:modelValue', contents),
        },
    });

    $editor.summernote('code', props.modelValue ?? '');
});

watch(() => props.modelValue, (value) => {
    const $editor = window.jQuery(editor.value);

    if ((value ?? '') !== $editor.summernote('code')) {
        $editor.summernote('code', value ?? '');
    }
});

onBeforeUnmount(() => {
    window.jQuery(editor.value).summernote('destroy');
});
</script>

<template>
    <textarea ref="editor"></textarea>
</template>
