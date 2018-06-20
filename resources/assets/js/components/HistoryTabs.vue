<template>
    <v-layout column fill-height class="blue-grey darken-4 history">
        <v-tabs v-model="tabModel" slider-color="primary" color="blue-grey darken-2">
            <v-tab :href="`#tab-history`">
                History
            </v-tab>
            <v-tab :href="`#tab-liked`" v-if="userLoggedIn">
                Liked
            </v-tab>
        </v-tabs>
        <v-tabs-items v-model="tabModel">
            <v-tab-item :id="`tab-history`">
                <history></history>
            </v-tab-item>
            <v-tab-item :id="`tab-liked`" v-if="userLoggedIn">
                <liked-runs-tab-content></liked-runs-tab-content>
            </v-tab-item>
        </v-tabs-items>
    </v-layout>
</template>

<script>
    export default {

        data() {
            return  {
                tabModel : 'tab-history'
            }
        },
        methods: {

        },
        computed: {
            userLoggedIn() {
                return this.$store.state.userLoggedIn
            }
        }
    }
</script>
<style scoped>

</style>
