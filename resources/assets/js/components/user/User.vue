<template xmlns="http://www.w3.org/1999/html">
    <v-layout justify-end space-between column fill-height>
        <div>
            <h1 class="headline" v-if="userLoggedIn">
                {{user.userName}}
                <v-btn small @click="logout" :loading="logoutLoading" :disabled="logoutLoading" class="d-inline-block">Logout</v-btn>
            </h1>
            <v-layout row justify-space-between v-if="!userLoggedIn">
                <login v-if="!userLoggedIn" class="d-inline-block"></login>
                <register v-if="!userLoggedIn" class="d-inline-block"></register>
            </v-layout>
        </div>
    </v-layout>
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
            },
            user() {
                return this.$store.state.user
            }
        }
    }
</script>
<style>

</style>
