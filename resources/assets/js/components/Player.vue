<template>
    <div class="col-md-10">
        <youtube :video-id="youtubeId"></youtube>
        <twitch-player :video="twitchId"></twitch-player>
    </div>
</template>

<script>

    export default {
        mounted() {
            console.log('Component mounted.')
        },
        data() {
            return {
                youtubeId: 'LW9yIR4GoVU',
                twitchId: '251198957'
            }
        },
        methods: {
          changeVideo() {
              this.youtubeId = 'ARtSqjel6Ho'
          }
        },
        computed: {
            run() {
                return this.$store.state.activeRun;
            }
        }
    }
</script>
