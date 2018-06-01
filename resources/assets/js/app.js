
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
        runHistory: [],
        count:'2'
    },
    mutations: {
        setRun(state, payload) {
            console.log('payload')
            state.activeRun = payload
           // state.runHistory.push(payload)
        },
        setGameCount(state, payload) {
            state.count = payload
        },
        increment(state) {
            state.count++
        }
    }
});

Vue.component('player', require('./components/Player.vue'));
Vue.component('sidebar', require('./components/Sidebar.vue'));
Vue.component('new-run', require('./components/NewRun.vue'));
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
