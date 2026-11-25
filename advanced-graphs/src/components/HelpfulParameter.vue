<!-- HelpfulParameter.vue -->
<template>
    <div class="AG-param">
      <div class="AG-param-label">
        <span>{{ labelText }}</span>
        <button
          type="button"
          class="AG-param-help"
          :title="helpLabel"
          :aria-label="helpLabel"
          @click="showModal = true">
          <i class="fa fa-question-circle" aria-hidden="true"></i>
        </button>
      </div>
      <div class="AG-param-control">
        <slot></slot>
      </div>
      <help-modal v-if="showModal" :title="labelText" @close="showModal = false">
        <p class="mb-0">{{ helpText }}</p>
      </help-modal>
    </div>
  </template>

  <script>
  import HelpModal from './HelpModal.vue';

  export default {
    name: 'HelpfulParameter',
    components: {
      HelpModal
    },
    // module is optional so this component still works outside the dashboard editor
    inject: {
      module: { default: null },
    },
    props: {
      labelText: {
        type: String,
        required: true
      },
      helpText: {
        type: String,
        required: true
      }
    },
    data() {
      return {
        showModal: false
      };
    },
    computed: {
      helpLabel() {
        const help = this.module ? this.module.tt('editor_help') : 'Help';
        return `${help}: ${this.labelText}`;
      },
    },
  };
  </script>

  <style scoped>
  .AG-param {
    display: flex;
    flex-direction: column;
    margin-bottom: 0.9rem;
  }

  .AG-param-label {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.3rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #343a40;
  }

  .AG-param-help {
    background: none;
    border: none;
    padding: 0;
    margin: 0;
    font: inherit;
    line-height: 1;
    color: #6c757d;
    cursor: pointer;
  }

  .AG-param-help:hover,
  .AG-param-help:focus-visible {
    color: #0d6efd;
  }

  .AG-param-control {
    display: flex;
    flex-direction: column;
  }
  </style>
