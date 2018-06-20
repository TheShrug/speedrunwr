<template>
    <v-btn outline color="primary" block @click="likeRun()" :loading="loading" :disabled="loading">
        <v-icon left>favorite</v-icon>
        <span v-if="liked">Unlike</span>
        <span v-if="!liked">Like</span>
    </v-btn>
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

                    console.log(response);


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