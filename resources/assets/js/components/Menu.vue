<template>
    <v-layout column justify-space-between fill-height>
        <v-flex xs2>
            <h1 class="logo"><a href="/" title="Speedrun">Speedrun<span title="Web Randomizer? World Records?">wr</span></a></h1>
            <new-run></new-run>
        </v-flex>
        <v-flex xs8>
            <history-tabs></history-tabs>
        </v-flex>
        <v-flex xs2>
            <user></user>
        </v-flex>
    </v-layout>
</template>

<script>
    export default {
        data() {
            return  {
                loginDialog: false,
            }
        },
        methods: {

        }
    }
</script>
<style scoped>
    .logo { margin-bottom: 6px;}
    .logo a { color: #fff; text-decoration: none;}
    .logo span {font-style:italic;-webkit-text-stroke-width: 2px; -webkit-text-stroke-color: #F94645; }
</style>
