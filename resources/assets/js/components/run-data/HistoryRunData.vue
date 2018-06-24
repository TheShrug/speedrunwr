<template>
    <div>
        <v-btn class="history-link " flat :color="color" block @click="clicked" v-bind:class="{ active : currentlyPlaying}">
            <v-card flat>
                <v-card-title>
                    <v-layout row wrap>
                        <v-flex xs12>
                            <h3 class="mb-0" v-if="runGameName">{{runGameName}}</h3>
                        </v-flex>
                        <v-flex xs12><div v-if="runCategoryName">{{runCategoryName}}<span v-if="runLevel"> - {{runLevel.name}}</span></div></v-flex>
                        <v-flex xs12><div v-if="runTime">{{runTime}}</div></v-flex>
                        <v-flex xs12><div v-if="runPlayerName">
                            By {{runPlayerName}}
                        </div></v-flex>
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
                if(this.run.players.data[0].rel === 'guest'){
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
                return (this.record === this.$store.state.activeRun)
            },
            color() {
                return 'white'
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
    .history-link { height: 100%; width: 100%;text-align: left; text-transform: none; margin: 0;box-shadow:none !important;}
    .history-link.active {border:1px solid rgba(255, 255, 255, 0.34);}
    .history-link .btn__content { padding: 0; white-space:normal;}
    .history-link .card { width: 100%; background: none;}
</style>