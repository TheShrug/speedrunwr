<template>
    <div class="history-container">
        <v-btn flat small color="white" v-on:click="toggleVisibility" class="toggle-button" :class="{'hidden-md-and-up': $vuetify.breakpoint.mdAndUp && visible}">
            <v-icon v-if="!visible">menu</v-icon><v-icon v-if="visible">close</v-icon> History
        </v-btn>
        <transition name="slide">
            <v-layout column fill-height class="blue-grey darken-4 history" v-show="visible">
                <v-tabs v-model="tabModel" slider-color="primary" color="blue-grey darken-2">
                    <v-tab :href="`#tab-history`">
                        History
                    </v-tab>
                    <v-tab :href="`#tab-liked`">
                        Liked
                    </v-tab>
                </v-tabs>
                <v-tabs-items v-model="tabModel" >
                    <v-tab-item :id="`tab-history`" >
                        <history></history>
                        <v-flex v-if="historyCount < 1" pa-3>
                            Start watching runs and your session history will be stored here.
                        </v-flex>
                    </v-tab-item>
                    <v-tab-item :id="`tab-liked`" >
                        <liked-runs-tab-content v-if="userLoggedIn"></liked-runs-tab-content>
                        <v-flex v-if="!userLoggedIn" pa-3>
                            You must be logged in to view your liked runs.
                        </v-flex>
                    </v-tab-item>
                </v-tabs-items>
            </v-layout>
        </transition>
    </div>
</template>

<script>
    export default {
        data() {
            return  {
                tabModel : 'tab-history',
                visible: true
            }
        },
        methods: {
            toggleVisibility() {
                this.visible = (!this.visible)
            }
        },
        computed: {
            userLoggedIn() {
                return this.$store.state.userLoggedIn
            },
            historyCount() {
                return this.$store.state.runHistory.length
            }
        }
    }
</script>
<style scoped>
    .history-container { position: relative;}
    .toggle-button { position: absolute; right: 0; top: -30px; margin: 0;}
    .history {transition:all .3s; max-height: 900px;}
    .slide-enter { max-height: 0;}
    .slide-leave-to { max-height: 0;}
</style>
