<template>
    <v-form id="NewRun">
        <v-btn @click="getNewRun" block>
            New Run
        </v-btn>
        <v-checkbox :label="'Autoplay'" v-model="autoPlay"></v-checkbox>
    </v-form>
</template>

<script>
    import axios from 'axios'

    function getRecords(game) {
        var gameId = game.id
        return axios.get('https://www.speedrun.com/api/v1/games/' + gameId + '/records', {
            params: {
                'skip-empty': true,
                'top': 1,
                'scope': 'full-game',
                'skip-empty': true
            }
        })
        .then(function(response) {
            var randomCategory = Math.floor(Math.random() * response.data.length)
            return response.data;
        })
    }

    function getRun(id) {
        return axios.get('https://www.speedrun.com/api/v1/runs/' + id , {
            params: {
                'embed': 'players,category'
            }
        })
        .then(function(response) {
            return response.data;
        })
    }


    export default {
        mounted() {
            console.log('Component mounted.')
        },
        data() {
            return {
                autoPlay: false,
                active: false
            }
        },
        methods: {
            getNewRun() {
                var $this = this
                var randomGameNumber = Math.floor(Math.random() * $this.gameCount);
                axios.get('https://www.speedrun.com/api/v1/games', {
                    params: {
                        max: 1,
                        offset: randomGameNumber,
                    }
                })
                .then(function(response) {
                    var game = response.data.data[0]
                    return getRecords(game)
                })
                .then(function(response) {
                    var randomCategory = Math.floor(Math.random() * response.data.length)
                    var runId = response.data[randomCategory].runs[0].run.id
                    return getRun(runId)
                })
                .then(function(test) {
                    console.log('Response', test);
                })
                .catch(function(response) {
                    //console.log(response);
                })
            }
        },
        computed: {
            gameCount() {
                return this.$store.state.count
            }
        }
    }
</script>
