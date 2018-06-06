<template>
    <div>
        <div v-if="run.game.data.names.international">{{run.game.data.names.international}}</div>
        <div v-if="run.category.data.name">{{run.category.data.name}}</div>
        <div v-if="run.times.primary_t">{{runTime}}</div>
        <div v-if="run.players.data[0].names.international">By {{run.players.data[0].names.international}}</div>
    </div>
</template>

<script>
    var moment = require('moment')
    var momentDurationFormatSetup = require('moment-duration-format')
    export default {
        mounted() {
            console.log('Component mounted.')
        },
        props: {
            run: Object
        },
        methods: {
            HumanizedTime(seconds) {
                return 'test'
            }
        },
        computed: {
            runTime() {
                var time = moment.duration(this.run.times.primary_t, "seconds").format('d[d] h[hr] m[m] s.S[s]')
                return  time
            }
        }
    }
</script>
