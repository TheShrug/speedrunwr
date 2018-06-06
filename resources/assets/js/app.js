
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import Vue from 'vue'
import Vuex from 'vuex'
import Axios from 'axios'
import Vuetify from 'vuetify'
import VueYouTubeEmbed from 'vue-youtube-embed'
import VueTwitchPlayer from 'vue-twitch-player'
import 'babel-polyfill'

Vue.use(Vuex)
Vue.use(Vuetify)
Vue.use(VueYouTubeEmbed)
Vue.use(VueTwitchPlayer)

// window.Vue = require('vue');
// window.Vuex = require('vuex');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const store = new Vuex.Store({
    state: {
        activeRun: {},
        activeRunData: null,
        runHistory: []
    },
    mutations: {
        setRun(state, payload) {
            state.activeRun = payload
        },
        setGameCount(state, payload) {
            state.count = payload
        },
        setRunData(state, payload) {
            state.activeRunData = payload
        }
    },
    actions: {
        getFullRunData(state, payload) {
            Axios.get('https://www.speedrun.com/api/v1/runs/' + payload, {
                params: {
                    embed:'game,category,players'
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

    }
});
