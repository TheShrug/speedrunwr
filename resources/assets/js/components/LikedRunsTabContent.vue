<template>
    <v-layout column class="history" align-content-space-between fill-height>
        <v-flex>
            <liked-run-data v-for="run in activeRunHistory" v-bind:run="run" :key="run.id"></liked-run-data>
        </v-flex>
        <div class="text-xs-center pagination-container" v-if="likedRuns.length > perPage">
            <v-pagination :length="this.paginationLength" :total-visible="totalVisible" v-model="page"></v-pagination>
        </div>
    </v-layout>
</template>

<script>
    export default {
        mounted() {
            console.log('Component mounted.')
        },
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

            likedRuns() {
                return this.$store.state.likedRuns
            },

            paginationLength() {
                return Math.ceil((this.$store.state.likedRuns.length / this.perPage) - 1) + 1
            },
            activeRunHistory() {

                return this.likedRuns.slice((this.page - 1) * this.perPage, (this.page - 1) * this.perPage + this.perPage)
            }
        }
    }
</script>
<style>
    .pagination-container button { height: 25px; width: 25px;}
</style>
