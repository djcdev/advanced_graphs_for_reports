<!-- SavedModal.vue -->
<template>
    <base-modal
        :title="module.tt('saved_modal_dashboard_saved')"
        icon="fa-check"
        variant="success"
        @close="$emit('close')">
        <p class="AG-saved-message">{{ module.tt('saved_modal_dashboard_named_saved', name) }}</p>
        <template #footer>
            <button type="button" class="btn btn-outline-secondary" @click="$emit('close')">
                {{ module.tt('saved_modal_continue_editing') }}
            </button>
            <button type="button" class="btn btn-outline-primary" @click="viewList()">
                {{ module.tt('saved_modal_return_to_list') }}
            </button>
            <button type="button" class="btn btn-primary" data-autofocus @click="viewDash()">
                {{ module.tt('saved_modal_view_dashboard') }}
            </button>
        </template>
    </base-modal>
</template>

<script>
import BaseModal from './BaseModal.vue';

export default {
    name: 'SavedModal',
    components: { BaseModal },
    inject: ['module'],
    props: {
        name: {
            type: String,
            default: "No name provided",
        },
        list_link: {
            type: String,
            default: "#",
        },
        dash_link: {
            type: String,
            default: "#",
        },
    },
    emits: ['close'],
    methods: {
        viewList() {
            window.location.href = this.list_link;
        },
        viewDash() {
            window.location.href = this.dash_link;
        },
    }

};
</script>

<style scoped>
.AG-saved-message {
    margin: 0;
}
</style>
