<template>
    <v-layout column justify-space-between fill-height class="history">
        <history-run-data v-for="run in runHistory" v-bind:run="run.data" v-bind:record="run.record"></history-run-data>
    </v-layout>
</template>

<script>
    export default {
        mounted() {
            console.log('Component mounted.')
        },
        data() {
            return  {

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
            }
        }
    }
</script>
<style scoped>

</style>
