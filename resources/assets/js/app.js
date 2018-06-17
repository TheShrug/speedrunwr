
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import Vue from 'vue'
import Vuex from 'vuex'
import Axios from 'axios'
import Vuetify from 'vuetify'
import Vuelidate from 'vuelidate'

import LoadScript from 'vue-plugin-load-script';
import VueYouTubeEmbed from 'vue-youtube-embed'
import VueTwitchPlayer from 'vue-twitch-player'
import 'babel-polyfill'

Vue.use(LoadScript);
Vue.use(Vuex);
Vue.use(Vuetify)
Vue.use(VueYouTubeEmbed);
Vue.use(VueTwitchPlayer);
Vue.use(Vuelidate)

const store = new Vuex.Store({
    state: {
        activeRun: {},
        activeRunData: null,
        runHistory: [],
        videoEnded: false,
        user: null,
        userLoggedIn: false
    },
    mutations: {
        setRun(state, payload) {
            state.activeRun = payload
            state.videoEnded = false
        },
        setRunData(state, payload) {
            state.activeRunData = payload
        },
        endVideo(state) {
            state.videoEnded = true
        },
        setUser(state, payload) {
            state.user = payload
            if(payload !== null)
                state.userLoggedIn = true
        },
        logout(state) {
            state.user = null
            state.userLoggedIn = false
        }
    },
    actions: {
        getFullRunData(state, payload) {
            Axios.get('https://www.speedrun.com/api/v1/runs/' + payload, {
                params: {
                    embed:'game.players,category.players,players'
                }
            })
            .then(function(response) {
                state.commit('setRunData', response.data.data)
            })
            .catch(function(response) {

            })
        }
    }
});

Vue.component('player', require('./components/Player.vue'));
Vue.component('app-menu', require('./components/Menu.vue'));
Vue.component('new-run', require('./components/NewRun.vue'));
Vue.component('run-data', require('./components/RunData.vue'));
Vue.component('user', require('./components/User.vue'));
Vue.component('login', require('./components/Login.vue'));
Vue.component('register', require('./components/Register.vue'));
Vue.component('message', require('./components/Message.vue'));
Vue.component('resend-email', require('./components/ResendEmail.vue'));
Vue.component('twitch-player', VueTwitchPlayer);


const app = new Vue({
    el: '#app',
    store,
    beforeMount() {

    },
    mounted() {
        this.getUser()

    },
    methods: {
        getUser() {
            let $this = this
            Axios.get(
                '/user'
            )
            .then(function(response) {
                $this.$store.commit('setUser', response.data.user)
            })
            .catch(function(response) {

            })
        }
    }
});
