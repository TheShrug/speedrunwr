
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
import Vuebar from 'vuebar'

import LoadScript from 'vue-plugin-load-script';
import VueYouTubeEmbed from 'vue-youtube-embed'
import VueTwitchPlayer from 'vue-twitch-player'
import 'babel-polyfill'

Vue.use(LoadScript);
Vue.use(Vuex);
Vue.use(Vuetify)
Vue.use(VueYouTubeEmbed);
Vue.use(VueTwitchPlayer);
Vue.use(Vuelidate);
Vue.use(Vuebar);

const store = new Vuex.Store({
    state: {
        activeRun: {},
        activeRunData: null,
        runHistory: [],
        videoEnded: false,
        user: null,
        userLoggedIn: false,
        likedRuns: null
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
        },
        addRunDataToHistory(state, payload) {
            state.runHistory.unshift(payload)
        },
        setLikedRuns(state, payload) {
            state.likedRuns = payload
        }
    },
    actions: {
        getFullRunData(state, payload) {
            Axios.get('https://www.speedrun.com/api/v1/runs/' + payload.runId, {
                params: {
                    embed:'game.players,category.players,players,level'
                }
            })
            .then(function(response) {
                state.commit('setRunData', response.data.data)
                state.commit('addRunDataToHistory', {data: response.data.data, record: payload.record})
            })
            .catch(function(response) {

            })
        }
    }
});

Vue.component('player', require('./components/Player.vue'));
Vue.component('app-menu', require('./components/Menu.vue'));
Vue.component('new-run', require('./components/NewRun.vue'));
Vue.component('user', require('./components/User.vue'));
Vue.component('login', require('./components/Login.vue'));
Vue.component('register', require('./components/Register.vue'));
Vue.component('message', require('./components/Message.vue'));
Vue.component('resend-email', require('./components/ResendEmail.vue'));
Vue.component('forgot-password', require('./components/ForgotPassword.vue'));
Vue.component('password-reset-form', require('./components/PasswordResetForm.vue'));
Vue.component('history-tabs', require('./components/HistoryTabs.vue'));
Vue.component('history', require('./components/History.vue'));
Vue.component('like-run', require('./components/LikeRun.vue'));
Vue.component('liked-runs-tab-content', require('./components/LikedRunsTabContent.vue'));
Vue.component('twitch-player', VueTwitchPlayer);



Vue.component('player-run-data', require('./components/run-data/PlayerRunData.vue'));
Vue.component('history-run-data', require('./components/run-data/HistoryRunData.vue'));
Vue.component('liked-run-data', require('./components/run-data/LikedRunData.vue'));


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
                $this.$store.commit('setLikedRuns', response.data.likedRuns)

            })
            .catch(function(response) {

            })
        }
    }
});
