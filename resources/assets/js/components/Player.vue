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
    export default {
        mounted() {
        },
        methods: {
            endVideo() {
                this.$store.commit('endVideo')
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
            }
        }
    }
</script>

<style>
    .player-container > * { position: relative; overflow: hidden; padding-top: 56.25%;}
    .player-container iframe {position: absolute;top: 0;left: 0;width: 100%;height: 100%;border: 0;}
</style>