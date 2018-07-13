<template>
    <v-layout column justify-space-between fill-height>
        <div>
            <h1 class="logo"><a href="/" title="Speedrun">Speedrun<span title="Web Randomizer? World Records?">wr</span></a></h1>
            <new-run></new-run>
        </div>
        <div>
            <history-tabs></history-tabs>
        </div>
        <div class="user" :class="{'fixed-bottom': $vuetify.breakpoint.smAndDown}">
            <user></user>
        </div>
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
<style>
    .logo { margin-bottom: 6px;}
    .logo a { color: #fff; text-decoration: none;}
    .logo span {font-style:italic;-webkit-text-stroke-width: 2px; -webkit-text-stroke-color: #F94645; }
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    input:-webkit-autofill:active {
        transition: background-color 5000s ease-in-out 0s;
        -webkit-text-fill-color: #fff !important;
    }
    .user {
        margin-top: auto;
    }
    .user.fixed-bottom {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #212121;
        padding: 0 5px;
        z-index: 99;
    }
</style>
