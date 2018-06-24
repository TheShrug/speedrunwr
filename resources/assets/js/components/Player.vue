<template>
    <div>
        <div class="player-container">
            <youtube v-if="youtubeId" :video-id="youtubeId" class="youtube" :player-vars="{autoplay:1}" @ended="endVideo" @error="endVideo"></youtube>
            <twitch-player v-if="twitchId" :video="twitchId" class="twitch" @ended="endVideo" width="640" height="390"></twitch-player>
        </div>
        <player-run-data v-bind:run="runData" v-if="runData" :run-comment="true"></player-run-data>

    </div>
</template>

<script>
    import Axios from 'axios'
    export default {
        mounted() {
            console.log('mountedtest');
            this.getRunDetails(this.$route.params.id);
        },
        methods: {
            endVideo() {
                this.$store.commit('endVideo')
            },
            getRunDetails(runId) {
                let $this = this
                // check if its different than the current run

                if($this.$store.state.activeRun.runId !== runId) {
                    // check if it is a record or is a liked run
                    Axios.get('/api/findRun', {
                        params: {
                            runId,
                        }
                    }).then(function(response) {

                        $this.$store.commit('setRun', response.data)
                        $this.$store.dispatch('getFullRunData', {runId: response.data.runId, 'record' : response.data})

                    }).catch(function(error) {
                        if(error.response.status === 404) {
                            console.log('Could not find');
                        }
                    }).then(function() {

                    });
                }



            }
        },
        computed: {
            run() {
                return this.$store.state.activeRun;
            },
            youtubeId() {
                return this.$store.state.activeRun.youtubeId
            },
            twitchId() {
                if(this.$store.state.activeRun.twitchId) {
                    return 'v' + this.$store.state.activeRun.twitchId
                } else {
                    return null
                }
            },
            runData() {
                return this.$store.state.activeRunData
            },
            userLoggedIn() {
                return this.$store.state.userLoggedIn
            },
        },
        watch: {
            $route: function (route) {
                if(route.params.id !== this.$store.state.activeRun.runId) {
                    this.getRunDetails(route.params.id)
                }

            }
        }
    }
</script>

<style>
    .player-container > * { position: relative; overflow: hidden; padding-top: 56.25%;}
    .player-container iframe {position: absolute;top: 0;left: 0;width: 100%;height: 100%;border: 0;}
</style>