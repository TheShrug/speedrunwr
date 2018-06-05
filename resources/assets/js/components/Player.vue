<template>
    <div class="col-md-10">
        <youtube v-if="youtubeId" :video-id="youtubeId"></youtube>
        <twitch-player v-if="twitchId" :video="twitchId"></twitch-player>
        <run-data v-bind:run="runData" v-if="runData"></run-data>
    </div>
</template>

<script>

    export default {
        mounted() {
            console.log('Component mounted.')
        },
        methods: {
          changeVideo() {
                  this.youtubeId = 'ARtSqjel6Ho'
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
                return this.$store.state.activeRun.twitchId
            },
            runData() {
                return this.$store.state.activeRunData
            }
        }
    }
</script>
