<?php get_header(); ?>
<style>
    body {
        background-color: #1e2122;
        margin: 0;
        padding: 0;
        font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
    }
    a{text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.2);}
    a:hover{opacity: 0.65;}
    .mainmsg{
        position: fixed; top: 0; left: 0; right:0;
        z-index: 10;
        color: #555;
        font-weight: 300;
        line-height: 24px;
        /*text-transform: uppercase;*/
        font-size: 16px;
        letter-spacing: 0.1em;
        text-align: right;
        padding: 10px;
        pointer-events: none;
    }
    .mainmsg b{font-weight: 700; font-size: 24px;}
    .fallback { display: none; }
    .game div {
        position: absolute;
        margin: 0;
        padding: 0;
    }
    .score-wrapper {
      display: none;
      position: absolute;
      top: -75px;
      left: 0;
      right: 0;
      overflow: hidden;
      height: 75px;
      background: #283133;
      text-align: center;
      line-height: 70px;
      font-family: "futura-pt",sans-serif;
      font-style: normal;
      font-weight: 700;
      color: #fff;
      font-size: 24px;
      letter-spacing: 0.1em;
    }
    .score-wrapper .score {
        position: static;
        display: inline-block;
        vertical-align: middle;
    }
    #board {
        top: 0px;
        left: 0px;
        bottom: 0;
        right:0px;
        width: 100%;
        background-color: #1e2122;
    }
    #divider {
        top: 0px;
        left: 50%;
        margin-left: -4px;
        width: 8px;
        height: 100%;
        background: #24292a;
    }
    .start,
    .start:focus,
    .start:active {
        position: absolute;
        top: 50%;
        left: 50%;
        display: block;
        padding: 0;
        margin: 0;
        border: 0;
        background: transparent;
        outline: 0;
        font-family: "futura-pt",sans-serif;
        font-style: normal;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 200px;
        letter-spacing: 0.1em;
        color: #fff!important;
        cursor: pointer;
        transform: translate(-50%, -50%);
        -moz-transition: color 0.3s linear;
        -o-transition: color 0.3s linear;
        -webkit-transition: color 0.3s linear;
        transition: color 0.3s linear;
    }
    .start:hover {
      color: #2ab5f0!important;
      -moz-transition: color 0.3s linear;
      -o-transition: color 0.3s linear;
      -webkit-transition: color 0.3s linear;
      transition: color 0.3s linear;
    }
    #status {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        display: block;
        padding: 0;
        margin: 0;
        border: 0;
        background: transparent;
        outline: 0;
        font-family: "futura-pt",sans-serif;
        font-style: normal;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 64px;
        letter-spacing: 0.05em;
        color: #fff!important;
        cursor: pointer;
        display: none;
        text-align: center;
        transform: translate(-50%, -50%);
    }
    .error-msg {
      display: inline-block;
      margin-right: 20px;
      font-family: "futura-pt",sans-serif;
      font-weight: bold;
      font-size: 13px;
      letter-spacing: 0.1em;
      color: #9fdaf3;
      vertical-align: middle;
    }
    .error-msg a:hover { color: white; }
    .touch .game {
      display: none!important;
    }
    .touch .fallback { display: block; }
    .touch body { background: white; }
</style>

<script type="text/javascript">
function getViewport() {
    var viewPortWidth;
    var viewPortHeight;
    if (typeof window.innerWidth != 'undefined') {
        viewPortWidth = window.innerWidth,
            viewPortHeight = window.innerHeight
    } else if (typeof document.documentElement != 'undefined' && typeof document.documentElement.clientWidth !=
        'undefined' && document.documentElement.clientWidth != 0) {
        viewPortWidth = document.documentElement.clientWidth,
            viewPortHeight = document.documentElement.clientHeight
    } else {
        viewPortWidth = document.getElementsByTagName('body')[0].clientWidth,
            viewPortHeight = document.getElementsByTagName('body')[0].clientHeight
    }
    return [viewPortWidth, viewPortHeight];
}

viewPort = getViewport();
windowWidth = viewPort[0];
windowHeight = viewPort[1];

var PLAY_WIDTH = windowWidth;
var PLAY_HEIGHT = windowHeight;
var LINE_WIDTH = 30;
var PADDLE_LENGTH = 150;
var ON_RIGHT = null;
var HUMAN_WINS = true;

var origWH = 800 * 400;
var newWH = PLAY_WIDTH * PLAY_HEIGHT;
var SCALE_FACTOR = Math.sqrt(newWH / origWH);
console.log(SCALE_FACTOR)

function makeRect(x, y, width, height, bgcolor, bradius) {
    var elem = document.createElement("div");
    var s = elem.style;
    s.position = "absolute";
    s.padding = "0";
    s.backgroundColor = bgcolor;
    s.top = y + "px";
    s.left = x + "px";
    s.width = width + "px";
    s.height = height + "px";
    return elem;
}
if (!document.addEventListener) {
    document.addEventListener = function(eventName, func) {
        eventName = "on" + eventName;
        if (this[eventName]) {
            var temp = this[eventName];
            this[eventName] = function() {
                console.log(event);
                temp(event);
                func(event);
            };
        } else {
            this[eventName] = function() {
                func(event)
            };
        }
    };
}

function Player(scoreDisplay, x, y, width, height) {
    this.x = x;
    this.y = y;
    this.startY = y;
    this.width = width;
    this.height = height;
    this.score = 0;
    this.scoreDisplay = scoreDisplay;
    this.elem = makeRect(this.x, this.y, this.width, this.height, "#fe2a61", this.width / 2);
    document.getElementById("board").appendChild(this.elem);
}
Player.prototype.reset = function() {
    this.moveTo(this.startY);
    this.score = 0;
    this.scoreDisplay.innerHTML = this.score;
};
Player.prototype.moveTo = function(y) {
    this.y = y;
    if (this.y > PLAY_HEIGHT - PADDLE_LENGTH) {
        this.y = PLAY_HEIGHT - PADDLE_LENGTH;
    } else if (this.y < 0) {
        this.y = 0;
    }
    this.elem.style.top = Math.floor(this.y) + "px";
};
Player.prototype.scored = function() {
    this.score++;
    this.scoreDisplay.innerHTML = this.score;
};
Player.prototype.AI = function(ball, onRight) {
    if ((ON_RIGHT && ball.dx < 0) || (!ON_RIGHT && ball.dx > 0)) {
        if (ball.y > this.y + this.height - LINE_WIDTH) {
            this.moveTo(this.y + 1 * SCALE_FACTOR);
        } else if (ball.y < this.y) {
            this.moveTo(this.y - 1 * SCALE_FACTOR);
        }
    }
};

Player.prototype.intersect = function(ball) {
    return ball.x + ball.width >= this.x && ball.x <= this.x + this.width && ball.y + ball.height >= this.y && ball.y < this.y + this.height;
};

Player.prototype.bounce = function(ball) {
    if (this.intersect(ball)) {

        ball.dx *= -1.1;

        var dy = (this.y + this.height / 2) - (ball.y + ball.height / 2);

        ball.dy -= dy / 333;

        return true;
    }

    return false;
}

function Ball(width, height) {
    this.x = -1000;
    this.y = 0;
    this.dx = 0;
    this.dy = 0;
    this.width = width;
    this.height = height;
    this.elem = makeRect(this.x, this.y, this.width, this.height, "#2ebcf9", this.width / 2);
    document.getElementById("board").appendChild(this.elem);
}

Ball.prototype.drop = function(direction, speed) {
    this.x = -100;
    this.y = 0;
    this.display();
    this.x = (PLAY_WIDTH - LINE_WIDTH) / 2;
    this.y = (PLAY_HEIGHT - LINE_WIDTH) / 2;

    var vel = 0.4 + 0.5 * speed / 8;

    var angle = (Math.random() * 2 - 1) * Math.PI / 4;

    this.dx = direction * Math.cos(angle) * vel;
    this.dy = Math.sin(angle) * vel;
};

Ball.prototype.display = function() {
    this.elem.style.top = Math.floor(this.y) + "px";
    this.elem.style.left = Math.floor(this.x) + "px";
};

Ball.prototype.advance = function() {
    this.x += this.dx;
    this.y += this.dy;
};

Ball.prototype.bounced = function(p1, p2, maxY) {
    if (this.y < 0 || this.y >= maxY) {
        this.dy *= -1;
    }
    return p1.bounce(this) || p2.bounce(this);
};

var p1, p2, ball, timer, lastFrame, startButtonLeft, startButtonRight, statusBox, state;
window.onload = function() {
    startButtonLeft = document.getElementById("start-left");
    startButtonRight = document.getElementById("start-right");
    statusBox = document.getElementById("status");
    ball = new Ball(LINE_WIDTH, LINE_WIDTH);
    p1 = new Player(
        document.getElementById("score1"), 1.5 * LINE_WIDTH, (PLAY_HEIGHT - PADDLE_LENGTH) / 2, LINE_WIDTH, PADDLE_LENGTH);
    p2 = new Player(
        document.getElementById("score2"),
        PLAY_WIDTH - LINE_WIDTH * 3, (PLAY_HEIGHT - PADDLE_LENGTH) / 2.5,
        LINE_WIDTH,
        PADDLE_LENGTH);
};



function moveHumanPlayer(y) {
    if (!ON_RIGHT) {
        p1.moveTo(y - PADDLE_LENGTH / 2);
    } else {
        p2.moveTo(y - PADDLE_LENGTH / 2);
    }
}
if (navigator.userAgent.indexOf("iPad") != -1 || navigator.userAgent.indexOf("iPod") != -1 || navigator.userAgent.indexOf("iPhone") != -1 || navigator.userAgent.indexOf("Android") != -1) {
    console.log("Is Mobile OS");
    document.addEventListener("touchmove", function(evt) {
        if (evt.touches.length > 0) {
            moveHumanPlayer(evt.touches[0].pageY);
        }
        evt.preventDefault();
    });
} else {
    console.log("Is Desktop OS");
    document.addEventListener("mousemove", function(evt) {
        moveHumanPlayer(evt.clientY);
    });
}

function titleScreen(delta) {}

function prePlay(delta) {
    if (delta < 1500) statusBox.innerHTML = document.getElementById("score1").innerHTML + ' &ndash; ' + document.getElementById("score2").innerHTML;
    else {
        statusBox.style.display = "none";
        state = updateGame;
        return true;
    }
    return false;
}

function updateGame(delta) {

    for (var i = 0; i < delta && state == updateGame; ++i) {
        ball.advance();
        if (!ball.bounced(p1, p2, PLAY_HEIGHT - LINE_WIDTH)) {
            var scoringPlayer = null;

            if (ball.x < p1.x) {
                scoringPlayer = p2;
            } else if (ball.x > p2.x) {
                scoringPlayer = p1;
            }
            if (scoringPlayer != null) {
                scoringPlayer.scored();
                ball.drop(scoringPlayer == p1 ? 1 : -1, p1.score + p2.score * SCALE_FACTOR);
                statusBox.style.display = "block";
                if (scoringPlayer.score < 3) {
                    state = prePlay;
                    console.log(scoringPlayer == p1);
                } else {
                    state = gameOver;
                    if ((ON_RIGHT && scoringPlayer == p2) || (!ON_RIGHT && scoringPlayer == p1)) {
                        HUMAN_WINS = true;
                    } else {
                        HUMAN_WINS = false;
                    }
                }
            }
        }
    }
    if (state == updateGame) {
        for (var i = 0; i < delta; i += AI_STEP) {
            if (!ON_RIGHT) {
                p2.AI(ball);
            } else {
                p1.AI(ball);
            }
        }
        ball.display();
    }

    return true;
}

var AI_STEP = 5;

function gameOver(delta) {
    clearInterval(timer);
    p1.reset();
    p2.reset();
    state = titleScreen;
    if (HUMAN_WINS) {
        statusBox.innerHTML = "You Won!<br><a href='http://bitwize.com.lb/#!/contact/' target='_blank'>Wanna Hire Us? ;)</a><br><br><a href='#' onclick='start(onRight=false);'>Play Again</a>";
    } else {
        statusBox.innerHTML = "Game Over<br><a href='http://bitwize.com.lb/#!/contact/' target='_blank'>Now Hire Us</a><br><br><a href='#' onclick='start(onRight=false);'>Play Again</a>";
    }
    return false;
}

function timerTick() {
    var currentFrame = new Date().getTime();
    var delta = currentFrame - lastFrame;
    if (state(delta)) {
        lastFrame = currentFrame;
    }
}

function start(onRight) {
    ON_RIGHT = onRight;
    statusBox.style.display = "none";
    startButtonLeft.style.display = "none";
    startButtonRight.style.display = "none";
    setTimeout(function() {
        statusBox.style.display = "block";
    }, 100);
    document.body.style.cursor = "none";
    lastFrame = new Date().getTime();
    ball.drop(1, 0);
    state = prePlay;
    timer = setInterval(timerTick, 33);
}
</script>
<div class="mainmsg"><b>404</b><br>content not found</div>
<div class="game">
    <div id="board">
        <div id="divider"></div>
        <div class="score-wrapper">
          <span class="error-msg">ERROR 404 – <a href="https://brightbrightgreat.com">BACK TO SITE</a></span>
          <div id="score1" class="score">0</div> &ndash;
          <div id="score2" class="score">0</div>
        </div>
        <div id="status" class="score">0 &ndash; 0</div>
        <div class="start" onclick="start(onRight = false)" id="start-left">Play</div>
        <button class="start" style="display: none;" id="start-right">Play</button>
    </div>
 </div>

 <div class="fallback content">
    <div class="inner-page 404">
        <div class="row">
          	<div class="large-12 column">
	            <h2 class="large-title"><hr> Error: 404 Not Found</h2>
        	</div>
    	</div>
	</div>
</div>


<?php get_footer(); ?>
