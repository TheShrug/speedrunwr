@extends('layouts.app')
@section('content')
    <div id="app">
        <v-app dark>
            <v-container fluid pa-0>
                <v-layout row wrap fill-height>
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
                        <router-view></router-view>
                    </v-flex>
                </v-layout>
            </v-container>
        </v-app>
    </div>
    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
