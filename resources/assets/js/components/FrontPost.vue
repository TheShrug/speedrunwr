<template>
    <v-layout row class="post" fill-height>
        <v-flex  sm8 xs12>
            <slot />
        </v-flex>
        <v-flex  sm4 hidden-xs-only>
            <div class="trophy" @click="clickTrophy">
                <i class="fa fa-trophy" title="Twenty three is number one!"></i>
                <span v-if="showNumber && !showPlace" class="number-display">{{ numberDisplay }}</span>
                <span v-if="showPlace" class="number-display place">
                    <span v-if="loadingScore">
                        <i class="fa fa-spin fa-spinner"></i>
                    </span>
                    <span v-if="!loadingScore">
                    #{{ place }}
                    </span>
                </span>
                <span v-if="showTimer" class="timer">{{ (timer / 1000).toFixed(3) }}</span>
            </div>
            <div class="shoes">
                <div><i class="fa fa-shoe-prints" data-id="1" @click="clickShoe($event)"></i></div>
                <div><i class="fa fa-shoe-prints" data-id="2" @click="clickShoe($event)"></i></div>
                <div><i class="fa fa-shoe-prints" data-id="3" @click="clickShoe($event)"></i></div>
            </div>
            <h1 class="large-icon"></h1>
        </v-flex>
    </v-layout>
</template>
<script>
    import Axios from 'axios'
    export default {
        data() {
            return {
                sound1: new Audio('/sounds/success.wav'),
                sound2: new Audio('/sounds/start.wav'),
                order: [2,3,1],
                step: 0,
                showNumber: false,
                numberDisplay: 3,
                gamePlaying: false,
                timer: 0,
                startTime: 0,
                showTimer: false,
                intervalRunning: false,
                gameOver: false,
                showPlace: false,
                place: 0,
                loadingScore: false,
                lockShoes: false
            }
        },
        methods: {
            clickTrophy() {
                if(this.gamePlaying === false) {
                  return null;
                } else {
                    this.numberDisplay -= 1
                }
            },
            clickShoe(event) {
                if(this.gameOver === false && this.lockShoes === false) {
                    let shoeId = parseInt(event.target.getAttribute('data-id'));
                    if(shoeId === this.order[this.step]) {
                        this.step += 1;
                        if(this.step === 3) {
                            this.startCountdown();
                        } else {
                            this.sound1.currentTime = 0;
                            this.sound1.play();
                        }
                    } else {
                        this.step = 0;
                    }
                }
            },
            startGame() {
                this.numberDisplay = 100;
                this.gamePlaying = true;
                this.startTime = Date.now();
                this.showTimer = true;
                this.intervalRunning = true;
                let $this = this;

                let interval = setInterval(function() {
                    if($this.intervalRunning === false) {
                        clearInterval(interval);
                    }
                    $this.timer = Date.now() - $this.startTime;
                    if($this.numberDisplay === 0) {
                        clearInterval(interval);
                        $this.endGame();
                    }
                }, 1);

            },
            startCountdown() {
                this.sound2.currentTime = 0;
                this.sound2.play();
                this.showNumber = true;
                this.intervalRunning = true;
                this.lockShoes = true;
                let $this = this;

                var interval = setInterval(function () {
                    if($this.intervalRunning === false) {
                        clearInterval(interval);
                    }
                    $this.numberDisplay -= 1
                    if($this.numberDisplay === 0) {
                        clearInterval(interval);
                        $this.startGame();
                    }
                }, 1000);
            },
            endGame() {
                this.gameOver = true;
                this.gamePlaying = false;
                this.loadingScore = true;
                let $this = this;
                Axios.post('/api/easterEgg', {
                    params: {
                        'time': this.timer
                    }
                }).then(function(response) {
                    $this.place = response.data.place;
                    $this.showPlace = true;
                }).catch(function(error) {

                }).then(function(){
                    $this.loadingScore = false;
                })
            }
        },
        destroyed: function() {
            this.intervalRunning = false;
        }
    }
</script>

<style scoped>
    a {
        text-decoration: none;
    }
    .post {
     font-size:140%;
    }
    .trophy {
        text-align: center;
        position: relative;
        -webkit-user-select: none; /* Safari */
        -moz-user-select: none; /* Firefox */
        -ms-user-select: none; /* IE10+/Edge */
        user-select: none; /* Standard */
    }
    .trophy:hover i.fa-trophy {
        color:#ffc140;
        transform:scale(1.1);
    }
    .trophy i.fa-trophy {
        font-size: 200px;
        color:#e8bf6a;
        margin-top: 30px;
        margin-bottom: 50px;
        transition:all .3s;
    }
    .shoes > div {
        display: block;
        text-align: center;
        margin-bottom: 30px;
    }
    .shoes i {
        font-size: 75px;
        transform: rotate(270deg);
        color:#272727;
        transition:all .3s;
    }
    .shoes i:hover {
        color:#444444;
    }
    .timer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        text-align: center;
    }
    .number-display {
        position: absolute;
        top: 20%;
        left: 0;
        right: 0;
        font-size: 64px;
        font-weight: 700;
        color: #303030;
    }
    .number-display.place  {
        top: 30%;
        font-size: 30px;
        color:#fff;
        text-shadow:0 0 3px #000;
    }

</style>