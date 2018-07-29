@extends('layouts.app')
@section('content')
    <div id="app">
        <v-app dark>
            <v-container fluid pa-0>
                <v-layout row wrap :class="{'fill-height': $vuetify.breakpoint.mdAndUp}">
                    <v-flex lg3 md4 sm12 class="blue-grey darken-3" pa-3>
                        <app-menu></app-menu>
                    </v-flex>
                    <v-flex lg9 md8 sm12 pa-3>
                        @if (isset($message))
                            <message type="info" message="{{$message}}">
                            </message>
                        @endif
                        @if(isset($passwordReset))
                            <password-reset-form token="{{$passwordReset}}"></password-reset-form>
                        @endif
                        <router-view v-cloak>
                            <h1 title="New name!">Speedrun Web Randomizer</h1>
                            <p>Find new interesting speedgames, speedruns, or speedrunners by watching <em>only the best</em>.</p>
                            <p>The speedrunning community is <a href="http://kotaku.com/awesome-games-done-quick-raises-a-record-breaking-2-2-1791230734" target="_blank" title="Awesome Games Done Quick Raises A Record Breaking $2.2 Million For Cancer Prevention">large</a>! The number of <a href="http://www.speedrun.com/statistics" title="" target="_blank">games</a>, categories and <a href="http://www.speedrun.com/statistics/users" title="Theres lots of em, trust me!">users</a> at <a href="http://www.speedrun.com" title="Thanks to speedrun.com for their API and making this whole project possible">speedrun.com</a> is getting larger every day.</p>
                            <h2>This is a tool to explore the community further.</h2>
                            <p>This site provides you with a world record from the speedrun.com api in hopes that: you will be entertained, you will follow the runners on twitch/youtube, or even find a new speedgame and compete for a world record!</p>
                            <p>Experiment with the settings to narrow down the results to the types of runs that you want to see. </p>
                            <h2>It's now easier than ever to find new speedgames to play.</h2>
                            <p>Narrow down your settings to find runs on platforms that you can play and start experiment with the rest of the settings to find the perfect game! Use the date filter to find old records to beat or find runs with little competition to join the race to the top!</p>
                            <h6 style="margin-top:100px;margin-bottom: 30px;"><a target="_blank" href="https://github.com/TheShrug/speedrunwr">Made with <span style="color:deeppink">❤</span></a> | <a href="mailto:webmaster@speedrunwr.com" title="Email Me">Contact</a></h6>
                        </router-view>
                    </v-flex>
                </v-layout>
            </v-container>
        </v-app>
    </div>
    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
