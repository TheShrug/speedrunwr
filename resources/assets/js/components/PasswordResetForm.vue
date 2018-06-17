<template>
    <div class="user-info">

        <v-dialog v-model="passwordResetFormDialog" max-width="500px" persistent>
            <form @submit="resetPassword()" v-on:submit.prevent>
                <v-card>
                    <v-card-title class="title">
                        Reset Password
                    </v-card-title>
                    <v-card-text>

                        <v-text-field
                                label="Email Address"
                                v-model="email"
                                :rules="[() => !$v.email.$invalid || 'Must be a valid email address']"
                        >
                        </v-text-field>
                        <v-text-field
                                label="Password"
                                type="password"
                                v-model="password"
                                required
                                :rules="[() => !$v.password.$invalid || 'Must be longer than 6 characters']"
                        >
                        </v-text-field>
                        <v-text-field
                                label="Repeat Password"
                                type="password"
                                v-model="repeatPassword"
                                required
                                :rules="[() => !$v.repeatPassword.$invalid || 'Passwords do not match']"
                        >
                        </v-text-field>
                        <v-alert v-model="alert" dismissible :type="alertType" outline>
                            {{alertMessage}}
                        </v-alert>


                    </v-card-text>
                    <v-card-actions>
                        <v-layout row wrap>
                            <v-flex xs6>
                                <v-btn color="primary" flat @click.stop="passwordResetFormDialog=false">Close</v-btn>
                            </v-flex>
                            <v-flex xs6 class="text-xs-right">
                                <v-btn color="primary" type="submit" :loading="loading" :disabled="loading">Reset Password</v-btn>
                            </v-flex>
                        </v-layout>
                    </v-card-actions>
                </v-card>
            </form>
        </v-dialog>
    </div>
</template>

<script>
    import { required, minLength, email, sameAs} from 'vuelidate/lib/validators'
    import Axios from 'axios'
    export default {
        mounted() {
        },
        props: ['token'],
        data() {
            return  {
                name: false,
                passwordResetFormDialog: true,
                email: '',
                password: '',
                repeatPassword: '',
                alert: false,
                alertType: 'warning',
                alertMessage: '',
                loading: false,
            }
        },
        validations: {
            email: {
                required,
                email
            },
            password: {
                required,
                minLength: minLength(6)
            },
            repeatPassword: {
                sameAsPassword: sameAs('password')
            }
        },
        methods: {
            resetPassword() {
                let $this = this;
                let params = {
                    email : this.$v.email.$model,
                    password : this.$v.password.$model,
                    password_confirmation : this.$v.repeatPassword.$model,
                    token : this.token

                };
                if(!this.$v.$invalid){
                    $this.loading = true;
                    Axios.post('/password/reset', params
                    ).then(function(response) {
                        if(response.data.message === 'success') {
                            window.location = '/';
                            return
                        }
                        if(response.data.message) {
                            $this.alert = true;
                            $this.alertMessage = response.data.message;
                            $this.alertType = response.data.messageType;
                            $this.clearForm();
                        }
                    }).catch(function(error) {

                    }).then(function() {
                        $this.loading = false;
                    })
                }
            }
        }
    }
</script>
<style scoped>

</style>
