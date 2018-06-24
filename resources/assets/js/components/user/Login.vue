<template>
    <span class="user-info">
        <v-btn small @click.stop="loginDialog = true" block>Login</v-btn>
        <v-dialog v-model="loginDialog" max-width="500px">
            <form @submit="loginUser()" v-on:submit.prevent>
            <v-card>
                <v-card-title class="title">
                    Login
                </v-card-title>
                <v-card-text>
                    <v-text-field
                            label="Username"
                            v-model="userName"
                    >
                    </v-text-field>
                    <v-text-field
                            label="Password"
                            type="password"
                            v-model="password"
                            required
                    >
                    </v-text-field>
                    <v-alert v-model="alert" dismissible :type="alertType" outline>
                        {{alertMessage}} <resend-email v-if="resendEmailLink" v-model="resendEmailDialog"></resend-email>
                    </v-alert>
                </v-card-text>
                <v-card-actions>
                    <v-layout row wrap>
                        <v-flex xs6>
                            <v-btn color="primary" flat @click.stop="loginDialog=false">Close</v-btn>
                            <forgot-password v-model="forgotPasswordDialog"></forgot-password>
                        </v-flex>
                        <v-flex xs6 class="text-xs-right">
                            <v-btn color="primary" type="submit" :loading="loading" :disabled="loading">Login</v-btn>
                        </v-flex>
                    </v-layout>
                </v-card-actions>
            </v-card>
            </form>
        </v-dialog>
    </span>
</template>

<script>
    import { required } from 'vuelidate/lib/validators'
    import Axios from 'axios'
    export default {
        data() {
            return  {
                name: false,
                loginDialog: false,
                userName: null,
                email: null,
                password: null,
                repeatPassword: null,
                alert: false,
                alertType: 'warning',
                alertMessage: '',
                loading: false,
                resendEmailLink: false,
                resendEmailDialog: false,
                forgotPasswordDialog: false
            }
        },
        validations: {
            userName: {
                required

            },
            password: {
                required
            }
        },
        methods: {
            loginUser(event) {

                let $this = this;
                let params = {
                    userName : this.$v.userName.$model,
                    password : this.$v.password.$model,

                };

                //finally lets register the user
                if(!this.$v.$invalid){
                    $this.loading = true;
                    Axios.post('/login', params
                    ).then(function(response) {
                        if(response.data.message === 'success') {
                            $this.$store.commit('setUser', response.data.user);
                            $this.$store.commit('setLikedRuns', response.data.likedRuns);
                        }
                        if(response.data.message == 'verifiedError') {
                            $this.alert = true;
                            $this.alertMessage = response.data.errorMessage;
                            $this.alertType = 'warning';
                            $this.resendEmailLink = true;
                        }

                    }).catch(function(error) {
                        if(error.response.data.errors) {
                            for (var prop in error.response.data.errors) {
                                $this.alert = true;
                                $this.alertMessage = error.response.data.errors[prop][0];
                                $this.alertType = 'warning'
                            }
                        }
                    }).then(function() {
                        $this.loading = false
                    })
                }
            }
        }
    }
</script>
<style scoped>

</style>
