<template>
    <v-layout column class="history" align-content-space-between fill-height>
        <v-flex>
            <history-run-data v-for="run in activeRunHistory" v-bind:run="run.data" v-bind:record="run.record" :key="run.data.id" class="flex"></history-run-data>
        </v-flex>
        <div class="text-xs-center pagination-container" v-if="runHistory.length > perPage">
            <v-pagination :length="this.paginationLength" :total-visible="totalVisible" v-model="page"></v-pagination>
        </div>
    </v-layout>
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
