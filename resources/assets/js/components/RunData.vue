<template>
    <div>
        <div v-if="run.category.data.name">{{run.category.data.name}}</div>
        <div v-if="run.game.data.names.international">{{run.game.data.names.international}}</div>
        <div v-if="run.players.data[0].names.international">By {{run.players.data[0].names.international}}</div>
    </div>
</template>

<script>

    export default {
        mounted() {
            console.log('Component mounted.')
        },
        props: ['run'],
        methods: {

        },
        computed: {

        }
    }
</script>
