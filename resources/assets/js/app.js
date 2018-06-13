
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import Vue from 'vue'
import Vuex from 'vuex'
import Axios from 'axios'
import Vuetify from 'vuetify'
import LoadScript from 'vue-plugin-load-script';
import VueYouTubeEmbed from 'vue-youtube-embed'
import VueTwitchPlayer from 'vue-twitch-player'
import 'babel-polyfill'

Vue.use(LoadScript);
Vue.use(Vuex);
Vue.use(Vuetify);
Vue.use(VueYouTubeEmbed);
Vue.use(VueTwitchPlayer);

const store = new Vuex.Store({
    state: {
        activeRun: {},
        activeRunData: null,
        runHistory: [],
        videoEnded: false
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
Vue.component('sidebar', require('./components/Sidebar.vue'));
Vue.component('new-run', require('./components/NewRun.vue'));
Vue.component('run-data', require('./components/RunData.vue'));
Vue.component('twitch-player', VueTwitchPlayer);


const app = new Vue({
    el: '#app',
    store,
    beforeMount() {

    },
    mounted() {

    },
    methods: {
        getNewRun(params) {

            var store = this.$store

            Axios.get('/api/getNewRun', {
                params: params
            })
            .then(function(response) {
                store.commit('setRun', response.data.record)
                store.dispatch('getFullRunData', response.data.record.runId)
            })
            .catch(function(response) {
                // TODO: do something on error
            })
        }
    }
});
