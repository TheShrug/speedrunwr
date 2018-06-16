<template>
    <div class="user-info">
        <v-btn small @click.stop="registerDialog = true">Register</v-btn>
        <v-dialog v-model="registerDialog" max-width="500px">
            <v-form @submit="registerUser()" v-on:submit.prevent>
                <v-card>
                    <v-card-title class="title">
                        Register
                    </v-card-title>
                    <v-card-text>
                        <v-text-field
                            label="Username"
                            v-model="userName"
                            :rules="[() => !$v.userName.$invalid || 'Must be less than 250 characters']"
                        >
                        </v-text-field>
                        <v-text-field
                            label="Email Address"
                            v-model="email"
                            required
                            :rules="[() => !$v.email.$invalid || 'Must be a valid email address']"

                        >
                        </v-text-field>
                        <v-text-field
                            label="Password"
                            type="password"
                            v-model="password"
                            :rules="[() => !$v.password.$invalid || 'Must be longer than 6 characters']"
                            required
                        >
                        </v-text-field>
                        <v-text-field
                            label="Repeat Password"
                            type="password"
                            :rules="[() => !$v.repeatPassword.$invalid || 'Passwords do not match']"
                            v-model="repeatPassword"
                            required
                        >
                        </v-text-field>
                    </v-card-text>
                    <v-card-actions>
                        <v-layout row wrap>
                            <v-flex xs6>
                                <v-btn color="primary" flat @click.stop="registerDialog=false">Close</v-btn>
                            </v-flex>
                            <v-flex xs6 class="text-xs-right">
                                <v-btn color="primary" type="submit" >Register</v-btn>
                            </v-flex>
                        </v-layout>
                    </v-card-actions>
                </v-card>
            </v-form>
        </v-dialog>
    </div>
</template>

<script>
    import { required, maxLength, minLength, email, sameAs } from 'vuelidate/lib/validators'
    import Axios from 'axios'
    export default {
        mounted() {
            console.log('Component mounted.')
        },
        data() {
            return  {
                name: false,
                registerDialog: false,
                userName: null,
                email: null,
                password: null,
                repeatPassword: null
            }
        },
        validations: {
            userName: {
                required,
                maxLength: maxLength(255)
            },
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
            registerUser() {

                let $this = this;

                let params = {
                    userName : this.$v.userName.$model,
                    email : this.$v.email.$model,
                    password : this.$v.password.$model,
                    password_confirmation : this.$v.repeatPassword.$model
                };

                //finally lets register the user
                if(!this.$v.$invalid){
                    Axios.post('/register', params
                    ).then(function(response) {
                        if(response.data.message === 'success') {
                            $this.$store.commit('setUser', response.data.user)
                        }
                    }).catch(function(response) {
                        console.log(response)
                    })
                }
            }
        }
    }
</script>
<style scoped>
    .sidebar-container { height:100%;}
</style>
