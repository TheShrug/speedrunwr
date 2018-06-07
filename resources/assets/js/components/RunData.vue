<template>
    <div>
        <v-layout row wrap class="run-details">
            <v-flex xs2 class="text-xs-center run-detail">
                <button class="fill-height"><i class="fa fa-thumbs-up"></i> Like</button>
            </v-flex>
            <v-flex xs4 class="run-detail">
                <div v-if="runGameName"><a :href="runGameLink">{{runGameName}}</a></div>
                <div v-if="runCategoryName">{{runCategoryName}}</div>
            </v-flex>
            <v-flex xs4 class="run-detail">
                <div v-if="runTime">{{runTime}}</div>
                <div v-if="runPlayerName">
                    By <a :href="runPlayerLink" target="_blank">{{runPlayerName}}</a>
                    <span v-if="runPlayerTwitch"><a :href="runPlayerTwitch"><i class="fab fa-twitch"></i></a></span>
                    <span v-if="runPlayerYoutube"><a :href="runPlayerYoutube"><i class="fab fa-youtube"></i></a></span>
                </div>
            </v-flex>
        </v-layout>

    </div>
</template>

<script>
    var moment = require('moment')
    var momentDurationFormatSetup = require('moment-duration-format')
    export default {
        mounted() {

        },
        data() {
            return {

            }
        },
        props: {
            run: Object
        },
        methods: {

        },
        computed: {
            runTime() {
                return (this.run.times.primary_t)
                    ? moment.duration(this.run.times.primary_t, "seconds").format('d[d] h[hr] m[m] s.S[s]')
                    : ''
            },
            runGameName() {
                return (this.run.game.data.names.international)
                    ? this.run.game.data.names.international
                    : ''
            },
            runGameLink() {
                return (this.run.game.data.weblink)
                    ? this.run.game.data.weblink
                    : ''
            },
            runCategoryName() {
                return (this.run.category.data.name)
                    ? this.run.category.data.name
                    : ''
            },
            runCategoryLink() {
                return (this.run.category.data.weblink)
                    ? this.run.category.data.weblink
                    : ''
            },
            runPlayerName() {
                if(this.run.players.data[0].rel == 'guest'){
                    return this.run.players.data['0'].name
                } else if(this.run.players.data[0].names.international) {
                    return this.run.players.data[0].names.international
                } else {
                    return ''
                }
            },
            runPlayerLink() {
                return (this.run.players.data[0].weblink)
                    ? this.run.players.data[0].weblink
                    : ''
            },
            runPlayerTwitch() {
                return (this.run.players.data[0].twitch)
                    ? this.run.players.data[0].twitch.uri
                    : ''
            },
            runPlayerYoutube() {
                return (this.run.players.data[0].youtube)
                    ? this.run.players.data[0].youtube.uri
                    : ''
            }
        }
    }
</script>
<style scoped>
    .run-details {
        background:#222C32;
    }
    .run-details .run-detail {
        padding: 5px;
    }
    button {
        width: 100%;
    }
</style>