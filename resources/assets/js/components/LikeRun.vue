<template>
    <div>
    <v-btn v-if="userLoggedIn" color="primary" flat block @click="likeRun()" :loading="loading" :disabled="loading">
        <v-icon left>favorite</v-icon>
        <span v-if="liked">Unlike</span>
        <span v-if="!liked">Like</span>
    </v-btn>
    <v-tooltip top v-if="!userLoggedIn">
        <v-btn
                slot="activator"
                color="primary"
                flat
                block
        >
            <v-icon left>favorite</v-icon>
            <span>Like</span>
        </v-btn>
        <span>You must be logged in to like runs!</span>
    </v-tooltip>
    </div>
</template>

<script>
    import Axios from 'axios'
    export default {
        mounted() {
            this.checkIfLiked()
        },
        data() {
            return {
                liked: false,
                loading: false
            }
        },
        props: {
            runId: String
        },
        methods: {
            likeRun() {

                let $this = this

                $this.loading = true

                let params = {
                    activeRun: this.activeRun
                };

                var store = this.$store

                Axios.post('/user/likeRun', {
                    params: params
                }).then(function(response) {

                    $this.liked = (response.data.message === 'liked')
                    $this.$store.commit('setLikedRuns', response.data.likedRuns)

                }).catch(function(response) {

                    // TODO: do something on error

                }).then(function(){

                    $this.loading = false

                })
            },
            checkIfLiked() {
                let $this = this
                $this.loading = true
                Axios.get('/user/likesRun', {
                    params : {
                        runId: $this.runId
                    }
                })
                .then(function(response) {
                    $this.liked = response.data.message

                })
                .catch(function(response) {
                    // TODO: do something on error
                }).then(function(){
                    $this.loading = false
                })
            }
        },
        computed: {
            userLoggedIn() {
                return this.$store.state.userLoggedIn
            },
            activeRun() {
                return this.$store.state.activeRun
            }
        },
        watch: {
            'runId': function() {
                this.checkIfLiked();
            },
            'userLoggedIn': function() {
                this.checkIfLiked();
            }
        }
    }
</script>
<style>

</style>