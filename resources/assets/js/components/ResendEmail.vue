<template>
    <div class="d-inline">
        <a @click.stop="resendEmailDialog = true">Resend Email</a>
        <v-dialog v-model="resendEmailDialog" max-width="500px">
            <form @submit="resendEmail()" v-on:submit.prevent ref="form">
                <v-card>
                    <v-card-title class="title">
                        Resend Verification Email
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
                                <v-btn color="primary" flat @click.stop="resendEmailDialog=false">Close</v-btn>
                            </v-flex>
                            <v-flex xs6 class="text-xs-right">
                                <v-btn color="primary" type="submit" :loading="loading" :disabled="loading">Resend Verification</v-btn>
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
        mounted() {
            console.log('Component mounted.')
        },
        data() {
            return  {
                emailAddress:'',
                resendEmailDialog:false,
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
            resendEmail() {

                let $this = this;
                let params = {
                    email : this.$v.emailAddress.$model,
                };
                console.log(params);
                if(!this.$v.$invalid) {
                    $this.loading = true
                    Axios.post('user/verify/resend',
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
