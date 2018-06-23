<template>
    <div class="d-inline">
        <a @click.stop="forgotPasswordDialog = true">Forgot Password</a>
        <v-dialog v-model="forgotPasswordDialog" max-width="500px">
            <form @submit="sendPasswordResetEmail()" v-on:submit.prevent ref="form">
                <v-card>
                    <v-card-title class="title">
                        Forgot Password
                    </v-card-title>
                    <v-card-text>
                        <v-text-field
                                label="Email"
                                v-model="emailAddress"
                                required
                                :rules="[() => !$v.emailAddress.$invalid || 'Must be valid email address']"
                        >
                        </v-text-field>
                        <v-alert v-model="alert" dismissible :type="alertType" outline>
                            {{alertMessage}}
                        </v-alert>
                    </v-card-text>
                    <v-card-actions>
                        <v-layout row wrap>
                            <v-flex xs6>
                                <v-btn color="primary" flat @click.stop="forgotPasswordDialog=false">Close</v-btn>
                            </v-flex>
                            <v-flex xs6 class="text-xs-right">
                                <v-btn color="primary" type="submit" :loading="loading" :disabled="loading">Send Email</v-btn>
                            </v-flex>
                        </v-layout>
                    </v-card-actions>
                </v-card>
            </form>
        </v-dialog>
    </div>
</template>

<script>
    import { required, email } from 'vuelidate/lib/validators'
    import Axios from 'axios'

    export default {
        data() {
            return  {
                emailAddress:'',
                forgotPasswordDialog:false,
                alert: false,
                alertType: 'warning',
                alertMessage: '',
                loading: false,
            }
        },
        validations: {
            emailAddress: {
                required,
                email
            }
        },
        methods: {
            sendPasswordResetEmail() {

                let $this = this;
                let params = {
                    email : this.$v.emailAddress.$model,
                };

                if(!this.$v.$invalid) {
                    $this.loading = true
                    Axios.post('/password/reset/sendEmail',
                        params
                    ).then(function(response) {
                        if(response.data.message) {
                            $this.alert = true;
                            $this.alertMessage = response.data.message;
                            $this.alertType = response.data.messageType;
                            $this.clearForm();
                        }
                    }).catch(function(error) {

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
