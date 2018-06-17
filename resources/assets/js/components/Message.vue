<template>
    <div>
        <v-alert v-model="alert" outline color="info" dismissible>
            {{this.message}}
        </v-alert>
    </div>
</template>

<script>
    export default {
        mounted() {
        },
        props: ['message'],
        data() {
            return {
                'alert': true
            }
        },
        methods: {

        },
        computed: {

        }
    }
</script>
