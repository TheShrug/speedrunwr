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

                var store = this.$store

                axios.get('/api/getNewRun', {
                    params: {

                    }
                })
                .then(function(response) {
                    store.commit('setRun', response.data.record)
                })
                .catch(function(response) {

                })
            }
        },
        computed: {
            gameCount() {
                return store.state.count
            }
        }
    }
</script>
