<template>
    <v-layout row class="post">
        <v-flex lg6 sm12>
            <h1>Speedrun World Records</h1>
            <p>Find new interesting speedgames, speedruns, or speedrunners by watching <em>only the best</em>.</p>
            <p>The speedrunning community is <a href="http://kotaku.com/awesome-games-done-quick-raises-a-record-breaking-2-2-1791230734" target="_blank" title="Awesome Games Done Quick Raises A Record Breaking $2.2 Million For Cancer Prevention">large</a>! The number of <a href="http://www.speedrun.com/statistics" title="" target="_blank">games</a>, categories and <a href="http://www.speedrun.com/statistics/users" title="Theres lots of em, trust me!">users</a> at <a href="http://www.speedrun.com" title="Thanks to speedrun.com for their API and making this whole project possible">speedrun.com</a> is getting larger every day.</p>
            <h2>This is a tool to explore the community further.</h2>
            <p>We've seen our fair share of <a  target="_blank" href="http://www.speedrun.com/sm64" title="Super Mairo 64 on speedrun.com">Super Mario 64</a>, <a  target="_blank" href="http://www.speedrun.com/oot" title="Ocarina of Time on speedrun.com">Ocarina of Time</a> and <a  target="_blank" href="http://www.speedrun.com/sms" title="Super Mario Sunshine on speedrun.com">Super Mario Sunshine</a> runs (if you haven't, you should). But we might not have given enough attention to some of the less well known speedrunners or communities.</p>
            <p>This site provides you with a world record from a random game and random category in hopes that: you will be entertained, you will follow the runners on twitch/youtube, or even find a new speedgame and compete for a world record!</p>
        </v-flex>
    </v-layout>
</template>

<script>
    import Axios from 'axios'
    export default {
        mounted() {
            console.log('mountedtest');

        },
        methods: {

        },
        computed: {
            run() {
                return this.$store.state.activeRun;
            },
            userLoggedIn() {
                return this.$store.state.userLoggedIn
            },
        },
        watch: {
            $route: function (route) {
                if(route.params.id !== this.$store.state.activeRun.runId) {

                }

            }
        }
    }
</script>

<style scoped>
 .post {font-size:150%;}
</style>