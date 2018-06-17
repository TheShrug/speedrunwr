@extends('layouts.app')
@section('content')
    <div id="app">
        <v-app dark>
            <v-container fluid>
                <v-layout row wrap fill-height>
                    <v-flex md3 xs12 pr-2>
                        <app-menu></app-menu>
                    </v-flex>
                    <v-flex md9 xs12 pl-2>
                        @if (isset($message))
                            <message type="info" message="{{$message}}">
                            </message>
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
