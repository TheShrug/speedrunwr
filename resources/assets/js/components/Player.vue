<template>
    <div>
        <div class="player-container">
            <youtube v-if="youtubeId" :video-id="youtubeId" class="youtube"></youtube>
            <twitch-player v-if="twitchId" :video="twitchId" class="twitch"></twitch-player>
        </div>
        <run-data v-bind:run="runData" v-if="runData"></run-data>
    </div>
</template>

<script>

    export default {
        mounted() {
            console.log('Component mounted.')
        },
        methods: {

        },
        computed: {
            run() {
                return this.$store.state.activeRun;
            },
            youtubeId() {
                return this.$store.state.activeRun.youtubeId
            },
            twitchId() {
                return this.$store.state.activeRun.twitchId
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