<template>
    <div>
        <v-layout row wrap class="run-details grey darken-3" pa-2>
            <v-flex xs2 class="text-xs-center run-detail" >
                <like-run v-bind:run-id="run.id"></like-run>
            </v-flex>
            <v-flex xs4 class="run-detail">
                <div v-if="runGameName"><a :href="runGameLink" target="_blank">{{runGameName}}</a></div>
                <div v-if="runCategoryName">
                    <a :href="runCategoryLink" target="_blank">{{runCategoryName}}</a>
                    <div v-if="runLevel">
                        <a :href="runLevel.weblink" target="_blank">{{runLevel.name}}</a>
                    </div>
                </div>
            </v-flex>
            <v-flex xs4 class="run-detail">
                <div v-if="runTime">{{runTime}}</div>
                <div v-if="runPlayerName">
                    By <a :href="runPlayerLink" target="_blank">{{runPlayerName}}</a>
                    <span v-if="runPlayerTwitch"><a :href="runPlayerTwitch" target="_blank"><i class="fab fa-twitch"></i></a></span>
                    <span v-if="runPlayerYoutube"><a :href="runPlayerYoutube" target="_blank"><i class="fab fa-youtube"></i></a></span>
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
            },
            runLevel() {
                if(this.run.level.data.weblink) {
                    return this.run.level.data
                } else {
                    return false;
                }
            }
        }
    }
</script>
<style scoped>
    .run-details .run-detail {

        font-size: 16px;
    }
    a {color:#E8BF6A;}
</style>