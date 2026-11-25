<!-- HelpModal.vue -->
<template>
    <base-modal
        :title="title"
        :icon="title ? 'fa-info' : ''"
        variant="info"
        @close="$emit('close')">
        <slot></slot>
        <template #footer>
            <button type="button" class="btn btn-primary" data-autofocus @click="$emit('close')">
                {{ doneLabel }}
            </button>
        </template>
    </base-modal>
  </template>

  <script>
  import BaseModal from './BaseModal.vue';

  export default {
    name: 'HelpModal',
    components: { BaseModal },
    inject: {
      module: { default: null },
    },
    props: {
      title: { type: String, default: '' },
    },
    emits: ['close'],
    computed: {
      doneLabel() {
        return this.module ? this.module.tt('modal_done') : 'Done';
      },
    },
  };
  </script>
