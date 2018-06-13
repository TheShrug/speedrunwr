<template>
    <v-form ref="form" id="NewRun">
        <v-btn @click="getNewRun" block>
            New Run
        </v-btn>
        <v-switch
            :label="`Autoplay`"
            v-model="autoPlay"
        ></v-switch>
        <v-switch
                :label="'Include Levels'"
                v-model="includeLevels"
        ></v-switch>
        <v-radio-group :label="'Video Type'" v-model="videoType" row default="0">
            <v-radio label="All" value="0"></v-radio>
            <v-radio label="Twitch" value="1"></v-radio>
            <v-radio label="Youtube" value="2"></v-radio>
        </v-radio-group>
        <v-checkbox label="Filter by Competition" v-model="competitionEnabled"></v-checkbox>
        <v-slider v-if="competitionEnabled" v-model="competition" max="3" step="1" prepend-icon="ac_unit" append-icon="whatshot" ticks :hide-details="true"></v-slider>
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
    </v-form>
</template>

<script>
    import axios from 'axios'

    export default {
        mounted() {

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
                competition: 0,
                competitionEnabled: 0
            }
        },
        methods: {
            getNewRun() {
                var $this = this
                var store = this.$store


                axios.get('/api/getNewRun', {
                    params: {
                        videoType: this.videoType,
                        includeLevels: this.includeLevels,
                        beforeDate: this.beforeDate,
                        afterDate: this.afterDate,
                        minRunLength: this.minRunLength,
                        maxRunLength: this.maxRunLength,
                        runCompetition: this.hotness

                    }
                })
                .then(function(response) {
                    if(response.data.record)
                        store.commit('clearRun')
                        store.commit('setRun', response.data.record)
                        store.dispatch('getFullRunData', response.data.record.runId)
                })
                .catch(function(response) {
                    // TODO: do something on error
                })
            },
            formatDate (date) {
                if (!date) return null
                const [year, month, day] = date.split('-')
                return `${month}/${day}/${year}`
            },
            clear() {
                this.$refs.form.reset()
                this.videoType = '0'
            }
        },
        computed: {
            gameCount() {
                return store.state.count
            },
            computedDateFormatted () {
                return this.formatDate(this.date)
            },
            hotness() {
                if(!this.competitionEnabled)
                    return null
                return this.competition
            }
        }
    }
</script>
<style scoped>
    .menu-slider {
        background: #222C32;
        padding-left: 16px;
        padding-top: 0;
    }
</style>