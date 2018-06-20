<template>
    <div>

        <v-layout row>
            <login v-if="!userLoggedIn"></login>
            <register v-if="!userLoggedIn"></register>
            <v-btn v-if="userLoggedIn" small @click="logout" :loading="logoutLoading" :disabled="logoutLoading">Logout</v-btn>
        </v-layout>

    </div>
</template>

<script>
    import Axios from 'axios'

    export default {

        data() {
            return  {
                loginDialog: false,
                logoutLoading: false,
            }
        },
        methods: {
            logout() {
                let $this = this
                $this.logoutLoading = true
                Axios.post(
                    '/logout'
                ).then(function(response) {
                    $this.$store.commit('logout')
                }).catch(function(response) {

                }).then(function(response) {
                    $this.logoutLoading = false
                })
            }
        },
        computed: {
            userLoggedIn() {
                return this.$store.state.userLoggedIn
            }
        }
    }
</script>
<style scoped>

</style>
