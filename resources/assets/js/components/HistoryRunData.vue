<template>
    <div>
        <v-btn class="history-link " :color="color" block @click="clicked" v-bind:class="{ active : currentlyPlaying}">
            <v-card flat>
                <v-card-title>
                    <v-layout row wrap>
                        <v-flex xs12>
                            <h3 class="mb-0" v-if="runGameName">{{runGameName}}</h3>
                        </v-flex>
                        <v-flex xs12><h4 v-if="runCategoryName">{{runCategoryName}}</h4></v-flex>
                        <v-flex xs12><h3 v-if="runTime">{{runTime}}</h3></v-flex>
                        <v-flex xs12><h4 v-if="runPlayerName">
                            By {{runPlayerName}}
                        </h4></v-flex>
                    </v-layout>
                </v-card-title>
            </v-card>
        </v-btn>
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
            run: Object,
            record: Object
        },
        methods: {
            clicked() {
                var store = this.$store
                store.commit('setRun', this.record)
                store.commit('setRunData', this.run)
            }
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
            currentlyPlaying() {
                if(this.record === this.$store.state.activeRun) {
                    return true
                } else {
                    return false
                }
            },
            color() {
                if(this.currentlyPlaying) {
                    return 'primary'
                } else {
                    return 'transparent'
                }
            }
        }
    }
</script>
<style>
    .run-hover-enter { background: #000;}
    .history-link { height: 100%; width: 100%;text-align: left; text-transform: none; margin: 0;box-shadow:none !important;}
    .history-link .btn__content { padding: 0; white-space:normal;}
    .history-link .card { width: 100%; background: none;}
</style>