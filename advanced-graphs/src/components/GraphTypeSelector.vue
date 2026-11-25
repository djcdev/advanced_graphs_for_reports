<!-- GraphTypeSelector.vue -->
<template>
    <div class="AG-graph-type-selector">
        <select
            v-model="selectedGraphType"
            class="form-select form-select-sm"
            :aria-label="module.tt('gts_select_a_graph_type')">
            <option :value="null" :selected="true"> -- {{ module.tt('gts_select_a_graph_type') }} --</option>
            <option v-for="(graphType, index) in availableGraphTypes"
            :key="index"
            :value="graphType"
            :selected="currentGraphType==graphType">
                {{ module.tt(graphType) }}
            </option>
        </select>
    </div>
</template>

<script>
export default {
    name: 'GraphTypeSelector',
    inject: ['module'],
    props: {
        currentGraphType: {
            type: String,
            default: null,
        },
        availableGraphTypes: {
            type: Array,
            default: null,
        }
    },
    data() {
        return {
            selectedGraphType: this.currentGraphType,
        };
    },
    watch: {
        selectedGraphType(newVal) {
            if (newVal)
                this.$emit('graphTypeChange', newVal);
        },
    },
};
</script>

<style scoped>
    .AG-graph-type-selector {
        min-width: 180px;
        flex: 1 1 auto;
    }

    .AG-graph-type-selector select {
        font-weight: 600;
    }
</style>
