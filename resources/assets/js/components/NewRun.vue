<template>
    <v-form ref="form" id="NewRun">
        <v-layout flex row>
            <div>
                <v-menu
                        :close-on-content-click="false"
                        v-model="mainMenu"
                        :nudge-right="0"
                        lazy
                        transition="scale-transition"
                        offset-y
                        full-width
                        max-width="330px"
                        min-width="290px"
                        content-class="blue-grey darken-4 pa-4"
                >

                    <v-switch
                            :label="'Include Levels'"
                            v-model="includeLevels"
                    ></v-switch>
                    <v-radio-group :label="'Video Type'" v-model="videoType" row default="0">
                        <v-radio label="All" value="0"></v-radio>
                        <v-radio label="Twitch" value="1"></v-radio>
                        <v-radio label="Youtube" value="2"></v-radio>
                    </v-radio-group>

                    <v-radio-group :label="'Competition'" v-model="competition" row default="0">
                        <v-radio label="All" value="0"></v-radio>
                        <v-radio label="Low" value="1"></v-radio>
                        <v-radio label="Med" value="2"></v-radio>
                        <v-radio label="High" value="3"></v-radio>
                    </v-radio-group>

                            <v-select
                            v-model="platform"
                            :items="platforms"
                            label="Platform"
                            multiple
                            chips
                    >

                    </v-select>
                    <v-layout row wrap>
                        <v-flex xs6>
                            <v-text-field type="number" label="Min Length" v-model="minRunLength" hint="In Minutes"></v-text-field>
                        </v-flex>
                        <v-flex xs6>
                            <v-text-field type="number" label="Max Length" v-model="maxRunLength" hint="In Minutes"></v-text-field>
                        </v-flex>
                    </v-layout>
                    <v-layout row wrap>
                        <v-flex xs6>
                            <v-menu
                                    :close-on-content-click="false"
                                    v-model="dateMenu2"
                                    :nudge-right="40"
                                    lazy
                                    transition="scale-transition"
                                    offset-y
                                    full-width
                                    max-width="290px"
                                    min-width="290px"
                            >
                                <v-text-field
                                        slot="activator"
                                        v-model="afterDate"
                                        label="Runs After"
                                        readonly
                                ></v-text-field>
                                <v-date-picker v-model="afterDate" no-title @input="dateMenu2 = false"></v-date-picker>
                            </v-menu>
                        </v-flex>
                        <v-flex xs6>
                            <v-menu
                                    :close-on-content-click="false"
                                    v-model="dateMenu1"
                                    :nudge-right="40"
                                    lazy
                                    transition="scale-transition"
                                    offset-y
                                    full-width
                                    max-width="290px"
                                    min-width="290px"
                            >
                                <v-text-field
                                        slot="activator"
                                        v-model="beforeDate"
                                        label="Runs Before"
                                        readonly
                                ></v-text-field>
                                <v-date-picker v-model="beforeDate" no-title @input="dateMenu1 = false"></v-date-picker>
                            </v-menu>
                        </v-flex>

                    </v-layout>
                    <v-btn @click="clear">clear</v-btn>
                    <v-btn @click="toggleMenu"
                           slot="activator"
                           class="thin-button"
                    ><v-icon>settings</v-icon></v-btn>
                </v-menu>

            </div>
            <v-flex>
                <v-btn @click="getNewRun" block
                       :loading="loading"
                       :disabled="loading"
                >
                    New Run
                </v-btn>
                <v-tooltip bottom v-model="couldNotFind">
                    <div slot="activator" class="activator"></div>
                    <span>Could not find a run with the current filters.</span>
                </v-tooltip>
            </v-flex>

        </v-layout>
        <v-flex mt-2>
            <v-switch
                    :label="`Autoplay`"
                    v-model="autoPlay"
            ></v-switch>
        </v-flex>
    </v-form>
</template>

<script>
    import Axios from 'axios'

    export default {
        mounted() {
            this.getPlatforms()
        },
        data() {
            return {
                autoPlay: false,
                videoType: '0',
                beforeDate: null,
                afterDate: null,
                dateFormatted: null,
                dateMenu1: false,
                dateMenu2: false,
                minRunLength: null,
                maxRunLength: null,
                includeLevels: false,
                competition: '0',
                competitionEnabled: 0,
                mainMenu: false,
                loading: false,
                platform: null,
                platforms: [],
                couldNotFind: false
            }
        },
        methods: {
            getNewRun() {
                let $this = this;
                $this.loading = true;
                $this.couldNotFind = false;
                let params = {
                    videoType: this.videoType,
                    includeLevels: this.includeLevels,
                    beforeDate: this.beforeDate,
                    afterDate: this.afterDate,
                    minRunLength: this.minRunLength,
                    maxRunLength: this.maxRunLength,
                    runCompetition: this.competition,
                    platform: this.platform

                };

                var store = this.$store

                Axios.get('/api/getNewRun', {
                    params: params
                }).then(function(response) {
                    $this.loading = false;
                    store.commit('setRun', response.data.record)
                    store.dispatch('getFullRunData', {runId: response.data.record.runId, 'record' : response.data.record})
                    $this.$router.push({path: '/run/' + response.data.record.runId})
                }).catch(function(error) {
                    if(error.response.status === 404)
                        $this.showNotFound()
                }).then(function(){
                    $this.loading = false
                })


            },
            showNotFound() {
                let $this = this
                this.couldNotFind = true
                setTimeout(function() {
                    $this.couldNotFind = false
                }, 5000)
            },
            formatDate (date) {
                if (!date) return null
                const [year, month, day] = date.split('-')
                return `${month}/${day}/${year}`
            },
            clear() {
                this.$refs.form.reset()
                this.videoType = '0'
            },
            toggleMenu() {
                if(this.mainMenu === false) {
                    this.mainMenu = true
                } else {
                    this.mainMenu = false
                }
            },
            getPlatforms() {
                let $this = this;

                Axios.get('https://www.speedrun.com/api/v1/platforms', {
                    params: {
                        max: '200'
                    }
                }).then(function(response) {
                    if(response.status === 200) {
                        response.data.data.forEach(function(platform) {
                            $this.platforms.push({
                                text: platform.name,
                                value: platform.id
                            })
                        })
                    }
                }).catch(function(error) {

                })
            }
        },
        computed: {
            gameCount() {
                return store.state.count
            },
            computedDateFormatted () {
                return this.formatDate(this.date)
            },
            videoEnded() {
                return this.$store.state.videoEnded
            },
        },
        watch: {
            'videoEnded': function() {
                if(this.$store.state.videoEnded === true && this.autoPlay === true) {
                    this.getNewRun();
                }
            }
        }
    }
</script>
<style>
    .settings-menu { background: #000;}
    .thin-button { min-width: 0; margin-left: 0;}
</style>