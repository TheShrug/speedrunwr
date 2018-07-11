<template>
    <v-layout column fill-height class="blue-grey darken-4 history">
        <v-tabs v-model="tabModel" slider-color="primary" color="blue-grey darken-2">
            <v-tab :href="`#tab-history`">
                History
            </v-tab>
            <v-tab :href="`#tab-liked`">
                Liked
            </v-tab>
        </v-tabs>
        <v-tabs-items v-model="tabModel" class="fill-height">
            <v-tab-item :id="`tab-history`" class="fill-height">
                <history></history>
            </v-tab-item>
            <v-tab-item :id="`tab-liked`" class="fill-height">
                <liked-runs-tab-content v-if="userLoggedIn"></liked-runs-tab-content>
                <v-flex v-if="!userLoggedIn" pa-3>
                    You must be logged in to view your liked runs.
                </v-flex>
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
