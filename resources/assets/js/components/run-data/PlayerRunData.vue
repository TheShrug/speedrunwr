<template>
    <div class="run-data-container" :class="{'mobile-mode': $vuetify.breakpoint.smAndDown }">
        <v-layout row wrap class="run-details grey darken-3" pa-1>
            <v-flex md3 sm3 xs12 class="run-detail">
                <like-run v-bind:run-id="run.id"></like-run>
            </v-flex>
            <v-flex md3 sm4 xs12 class="run-detail">
                <div v-if="runGameName"><p><a :href="runGameLink" target="_blank">{{runGameName}}</a></p></div>
                <div v-if="runCategoryName">
                    <p><a :href="runCategoryLink" target="_blank" >{{runCategoryName}}</a></p>
                    <p v-if="runLevel">
                        <a :href="runLevel.weblink" target="_blank">{{runLevel.name}}</a>
                    </p>
                </div>
            </v-flex>
            <v-flex md3 sm3 xs12 class="run-detail">
                <p v-if="runTime">{{runTime}}</p>
                <p v-if="runPlayerName">
                    By <a :href="runPlayerLink" target="_blank">{{runPlayerName}}</a>
                    <span v-if="runPlayerTwitch"><a :href="runPlayerTwitch" target="_blank"><i class="fab fa-twitch"></i></a></span>
                    <span v-if="runPlayerYoutube"><a :href="runPlayerYoutube" target="_blank"><i class="fab fa-youtube"></i></a></span>
                </p>
            </v-flex>
            <v-flex md3 sm2 xs12 class="run-detail">
                <p v-if="runPlatform">
                    {{runPlatform}}
                </p>
            </v-flex>
        </v-layout>
    </div>
</template>

<script>
    var moment = require('moment')
    var momentDurationFormatSetup = require('moment-duration-format')
    export default {
        props: {
            run: Object
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
            },
            runPlatform() {
                return (this.run.platform.data.name) ? this.run.platform.data.name : null
            }
        }
    }
</script>
<style scoped>
    .run-data-container.mobile-mode {
        margin-bottom: 80px;
    }
    .run-details .run-detail {
        font-size: 14px;
    }
    a {color:#E8BF6A;}
    p { margin-bottom: 5px;}
</style>