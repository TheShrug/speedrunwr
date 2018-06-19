<template>
    <v-layout column justify-space-between fill-height>
        <v-flex xs2>
            <h1>Speedrunwr.com</h1>
            <new-run></new-run>
        </v-flex>
        <v-flex xs8>
            <history-tabs></history-tabs>
        </v-flex>
        <v-flex xs2>
            <user></user>
        </v-flex>
    </v-layout>
</template>

<script>
    export default {
        mounted() {
            console.log('Component mounted.')
        },
        data() {
            return  {
                loginDialog: false,
            }
        },
        methods: {

        }
    }
</script>
<style scoped>
    .sidebar-container { height:100%;}
</style>
