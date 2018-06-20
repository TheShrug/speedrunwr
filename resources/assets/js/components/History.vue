<template>
    <div class="history">
        <history-run-data v-for="run in activeRunHistory" v-bind:run="run.data" v-bind:record="run.record" :key="run.data.id" ></history-run-data>
        <div class="text-xs-center pagination-container" v-if="runHistory.length > perPage">
            <v-pagination :length="this.paginationLength" :total-visible="totalVisible" v-model="page"></v-pagination>
        </div>
    </div>
</template>

<script>
    export default {

        data() {
            return  {
                page: 1,
                totalVisible: 7,
                perPage: 5
            }
        },
        methods: {

        },
        computed: {
            userLoggedIn() {
                return this.$store.state.userLoggedIn
            },
            runHistory() {
                return this.$store.state.runHistory
            },
            paginationLength() {
                return Math.ceil((this.$store.state.runHistory.length / this.perPage) - 1) + 1
            },
            activeRunHistory() {

                return this.runHistory.slice((this.page - 1) * this.perPage, (this.page - 1) * this.perPage + this.perPage)
            }
        }
    }
</script>
<style>
    .pagination-container button { height: 25px; width: 25px;}
</style>
