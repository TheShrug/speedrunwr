@extends('layouts.app')
@section('content')
    <div id="app">
        <v-app dark>
            <v-container fluid pa-0>
                <v-layout row wrap fill-height>
                    <v-flex md3 xs12 class="blue-grey darken-3" pa-3>
                        <app-menu></app-menu>
                    </v-flex>
                    <v-flex md9 xs12 pa-3>
                        @if (isset($message))
                            <message type="info" message="{{$message}}">
                            </message>
                        @endif
                        @if(isset($passwordReset))
                            <password-reset-form token="{{$passwordReset}}"></password-reset-form>
                        @endif
                        <player xs4></player>
                    </v-flex>
                </v-layout>
            </v-container>
        </v-app>
    </div>
    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
