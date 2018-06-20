<template>
    <div>
        <v-btn class="history-link " outline :color="color" block @click="clicked" v-bind:class="{ active : currentlyPlaying}" :loading="loading" :disabled="loading">
            <v-card flat>
                <v-card-title>
                    <v-layout row wrap v-if="runData">
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
    import Axios from 'axios'
    export default {
        mounted() {

            this.getRunData();
        },
        data() {
            return {
                runData: null,
                loading: false
            }
        },
        props: {
            run: Object
        },
        methods: {
            clicked() {
                var store = this.$store
                store.commit('setRun', this.run)
                store.commit('setRunData', this.runData)
            },
            getRunData() {
                let $this = this
                $this.loading = true
                Axios.get('https://www.speedrun.com/api/v1/runs/' + this.run.runId, {
                    params: {
                        embed:'game.players,category.players,players,level'
                    }
                }).then(function(response) {
                    $this.runData = response.data.data
                }).catch(function(response) {

                }).then(function() {
                    $this.loading = false
                })
            }

        },
        computed: {
            runTime() {
                return (this.runData.times.primary_t)
                    ? moment.duration(this.runData.times.primary_t, "seconds").format('d[d] h[hr] m[m] s.S[s]')
                    : ''
            },
            runGameName() {
                return (this.runData.game.data.names.international)
                    ? this.runData.game.data.names.international
                    : ''
            },
            runGameLink() {
                return (this.runData.game.data.weblink)
                    ? this.runData.game.data.weblink
                    : ''
            },
            runCategoryName() {
                return (this.runData.category.data.name)
                    ? this.runData.category.data.name
                    : ''
            },
            runCategoryLink() {
                return (this.runData.category.data.weblink)
                    ? this.runData.category.data.weblink
                    : ''
            },
            runPlayerName() {
                if(this.runData.players.data[0].rel === 'guest'){
                    return this.runData.players.data['0'].name
                } else if(this.runData.players.data[0].names.international) {
                    return this.runData.players.data[0].names.international
                } else {
                    return ''
                }
            },
            runPlayerLink() {
                return (this.runData.players.data[0].weblink)
                    ? this.runData.players.data[0].weblink
                    : ''
            },
            runPlayerTwitch() {
                return (this.runData.players.data[0].twitch)
                    ? this.runData.players.data[0].twitch.uri
                    : ''
            },
            runPlayerYoutube() {
                return (this.runData.players.data[0].youtube)
                    ? this.runData.players.data[0].youtube.uri
                    : ''
            },
            currentlyPlaying() {
                return (this.run.runId === this.$store.state.activeRun.runId)
            },
            color() {
                if(this.currentlyPlaying) {
                    return 'white'
                } else {
                    return 'transparent'
                }
            }
        }
    }
</script>
<style>
    .run-hover-enter { background: #000;}
    .history-link { height: 100%; width: 100%;text-align: left; text-transform: none; margin: 0;}
    .history-link .btn__content { padding: 0; white-space:normal;}
    .history-link .card { width: 100%; background: none;}
</style>