<template>
    <div class="user-info">
        <v-btn small @click.stop="loginDialog = true">Login</v-btn>
        <v-dialog v-model="loginDialog" max-width="500px">
            <form @submit="loginUser()" v-on:submit.prevent>
            <v-card>
                <v-card-title class="title">
                    Register
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

                </v-card-text>
                <v-card-actions>
                    <v-layout row wrap>
                        <v-flex xs6>
                            <v-btn color="primary" flat @click.stop="loginDialog=false">Close</v-btn>
                        </v-flex>
                        <v-flex xs6 class="text-xs-right">
                            <v-btn color="primary" type="submit">Login</v-btn>
                        </v-flex>
                    </v-layout>
                </v-card-actions>
            </v-card>
            </form>
        </v-dialog>
    </div>
</template>

<script>
    import { required } from 'vuelidate/lib/validators'
    import Axios from 'axios'
    export default {
        mounted() {
            console.log('Component mounted.')
        },
        data() {
            return  {
                name: false,
                loginDialog: false,
                userName: null,
                email: null,
                password: null,
                repeatPassword: null,
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
                    Axios.post('/login', params
                    ).then(function(response) {
                        if(response.data.message === 'success') {
                            $this.$store.commit('setUser', response.data.user);
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
