<template>
    <div class="sidebar-container">
        <h1>Speedrunwr.com</h1>
        <new-run></new-run>
        <user></user>
    </div>
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
