@extends('layouts.app')

@push('css')
    <style>
        canvas {
            margin: 0;
        }

        .field {
            display: flex;
            flex-direction: column;
        }

        #field {
            display: flex;
            flex-wrap: wrap;
        }

        #field .field {
            flex-basis: 50%;
            margin-bottom: 30px;
        }

        .field .cell {
            flex: 1 1;
            height: 4vw;
            width: 4vw;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
            cursor: pointer;
        }

        @media(max-width: 900px) {

            .field .cell {
                height: 6vw;
                width: 6vw;
            }
        }

        .field .field_row {
            display: flex;
            flex-grow: 1;
        }

        .field .field_row:nth-child(even) .cell:nth-child(odd) {
            background-color: #00000033
        }

        .field .field_row:nth-child(odd) .cell:nth-child(even) {
            background-color: #00000033
        }

        .field .piece_img_container {
            position: relative;
            user-select: none;
        }

        .field .piece_img_container .piece_img_container__block {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 100;
        }

        .field .cell img {
            object-fit: cover;
            height: 100%;
            width: 100%;
        }

        .background_kill {
            background-color: #F67874 !important;
        }

        .background_kill__hover {
            background-color: #e09895 !important;
        }

        .background_move {
            background-color: #EDB75E !important;
        }

        .background_move__hover {
            background-color: #f5d49f !important;
        }

        .background_self {
            background-color: #EEE9A0 !important;
        }

        .background_self__hover {
            background-color: #f3f0c9 !important;
        }

        .border-top {
            border-top: 1px solid black !important;
        }
        .border-left {
            border-left: 1px solid black !important;
        }
    </style>
@endpush
{{--
@push('js')
    <script>
        var socket;
        var game_id = Number("{{ $game?->id ?: 0 }}");
        var initializedWithSocket = false;
        var currentDraggable;
        var fieldLength = 8;

        $(document).ready(function() {
            socket = new WebSocket("ws://127.0.0.1:8090");

            socket.onopen = () => {
                initializedWithSocket = true;
                socket.send(JSON.stringify({
                    type: 'init',
                    game_id: game_id,
                    user_id: "{{ Auth::id() }}"
                }));
            }

            socket.onerror = () => {
                if (!initializedWithSocket)
                    fetch('/game/start')
                        .then(r => r.json())
                        .then(r => initGame(r));
            }

            socket.onclose = () => {
                console.log('Соединение закрыто');
            }
            socket.onmessage = (event) => {
                var data = JSON.parse(event.data);
                if (!data)
                    return;
                if (data.type == 'update' || data.type == 'init') {
                    initGame(data.data);
                } else if (data.type == 'get_moves') {
                    if (data.type == 'get_moves') {
                        showMoves(data.data);
                        showSelfCell(currentDraggable);
                    }
                }
            }
        });

        function showMoves(data) {
            data = Object.values(data);
            data.forEach(val => {
                $cellToMove = $(`.cell[data-x="${val.x}"][data-y="${val.y}"]`);

                if ($cellToMove.find('.piece_img_container').length>0) {
                    $(`.cell[data-x="${val.x}"][data-y="${val.y}"]`).addClass('background_kill');
                } else {
                    $(`.cell[data-x="${val.x}"][data-y="${val.y}"]`).addClass('background_move');
                }

            });
        }

        function showSelfCell(element) {
            $(element).parents('.cell').first().addClass('background_self');
        }

        function initGame(r) {
            $('.field').remove();
            let $table = $(`<div class="field"></div>`);
            let $tr;
            for(let j=0; j<fieldLength; j++) {
                $tr = $(`<div class="field_row${j == 0 ? ' border-top' : ''}"></div>`);
                for(let i=0; i<fieldLength; i++) {
                    $tr.append($(`
                        <div class="cell${i == 0 ? ' border-left' : ''}" data-x="${i+1}" data-y="${fieldLength-j}">
                        </div>
                    `));
                }
                $table.append($tr);
            }
            $('#field').append($table);

            r.forEach(val => {
                let $cell = $(`.cell[data-x="${val.pos_x}"][data-y="${val.pos_y}"]`);
                $cell.html(`
                    <div class="piece_img_container">
                        <img>
                        <div class="piece_img_container__block"></div>
                    </div>
                `);
                $(`img`, $cell).attr('src', val.image);

                let width = $('.piece_img_container').first().width();
                $('.piece_img_container', $cell).draggable({
                    cursorAt: {left: width/2, top: width/2},
                    start: function(event, ui) {
                        currentDraggable = this;
                        $(this).css('z-index', '1000');
                        if (!initializedWithSocket) {
                            $.ajax({
                                url: '/game/moves',
                                method: 'GET',
                                data: {
                                    id: val.id,
                                    type: val.type,
                                    _token: "{{ csrf_token() }}"
                                },
                                success: response => {
                                    showMoves(response);
                                },
                                error: response => {

                                },
                                complete: response => {
                                    showSelfCell(this);
                                }
                            });
                        } else {
                            socket.send(JSON.stringify({
                                'type': 'get_moves',
                                'id': val.id,
                                user_id: "{{ Auth::id() }}"
                            }));
                        }
                    },
                    stop: function(event, ui) {
                        $(this).css('z-index', '100');
                        let $cellUnderCursor = $(document.elementsFromPoint(event.pageX, event.pageY - $(document).scrollTop())).filter('.cell').first();
                        if (!$cellUnderCursor.length) {
                            $(this).attr('style', '');
                                ['background_kill', 'background_move', 'background_self'].forEach(val => {
                                    $(`.${val}`).removeClass(val).removeClass(val+"__hover");
                                });
                            return;
                        }

                        if (!initializedWithSocket) {
                            $.ajax({
                                url: '/game/move',
                                method: 'PATCH',
                                data: {
                                    id: val.id,
                                    x: $cellUnderCursor.data('x'),
                                    y: $cellUnderCursor.data('y'),
                                    _token: "{{ csrf_token() }}"
                                },
                                success: response => {
                                    if (response.length !== 0)
                                        $cellUnderCursor.html(this);
                                },
                                error: response => {

                                },
                                complete: response => {
                                    $(this).attr('style', '');
                                    ['background_kill', 'background_move', 'background_self'].forEach(val => {
                                        $(`.${val}`).removeClass(val).removeClass(val+"__hover");
                                    });
                                }
                            });
                        } else {
                            socket.send(JSON.stringify({
                                type: 'move',
                                id: val.id,
                                x: $cellUnderCursor.data('x'),
                                y: $cellUnderCursor.data('y'),
                                game_id: "{{ $game->id }}",
                                user_id: "{{ Auth::id() }}"
                            }));
                        }
                    }
                });
            });
        }

        $('body').delegate('.field', 'mousemove', function(event) {
            let $cellUnderCursor = $(document.elementsFromPoint(event.pageX, event.pageY - $(document).scrollTop())).filter('.cell').first();
            ['background_move', 'background_kill', 'background_self'].forEach(val => {
                $(`.${val}__hover`).removeClass(`${val}__hover`);
                if ($cellUnderCursor.hasClass(val))
                    $cellUnderCursor.addClass(`${val}__hover`);
            });
        });

        $(window).on('resize', function() {
            let width = $('.piece_img_container').width();
            $( '.piece_img_container' ).draggable( "option", "cursorAt", { left: width/2 } );
        })

        const app = {
            data() {
                return {
                    fieldLength: Number("{{ $gameRules->getFieldLength() }}")
                }
            }
        };

        Vue.createApp(app).mount('#app');
    </script>
@endpush --}}

@push('js')
    <script>
        const app = {
            data() {
                return {
                    fieldLength: Number("{{ $gameRules->getFieldLength() }}"),
                    pieces: {
                    },
                    initializedWithSocket: false,
                    socket: null,
                    game_id: [84, 85, 86, 87],
                    currentDraggable: null,
                    key: 0,
                    games: {},
                    queue: []
                }
            },
            created() {
                this.socket = new WebSocket("ws://127.0.0.1:8090");

                this.socket.onopen = () => {
                    this.initializedWithSocket = true;
                    this.socket.send(JSON.stringify({
                        type: 'init',
                        game_id: this.game_id[0],
                        user_id: "{{ Auth::id() }}"
                    }));
                    this.game_id.forEach(val => {
                        this.queue.push(JSON.stringify({
                            type: 'init',
                            game_id: val,
                            user_id: "{{ Auth::id() }}"
                        }));
                    });
                }

                this.socket.onerror = () => {
                    if (!this.initializedWithSocket)
                        fetch('/game/start')
                            .then(r => r.json())
                            .then(r => initGame(r));
                }

                this.socket.onclose = () => {
                    console.log('Соединение закрыто');
                }
                this.socket.onmessage = (event) => {
                    var data = JSON.parse(event.data);
                    if (!data)
                        return;
                    if (data.type == 'update' || data.type == 'init') {
                        this.initGame(data.data);
                        if (this.queue.length > 0) {
                            this.socket.send(this.queue.pop());
                        }
                    } else if (data.type == 'get_moves') {
                        if (data.type == 'get_moves') {
                            this.showMoves(data.data.game, data.data.pieces);
                            this.showSelfCell(data.data.game);
                        }
                    }
                }
            },
            updated() {
                this.bindDraggale();
            },
            methods: {
                initGame(r) {
                    this.games[r.game] = {};
                    let pieces;
                    this.games[r.game].pieces = {};
                    r.pieces.forEach(val => {
                        if (this.games[r.game].pieces[val.pos_y] === undefined)
                            this.games[r.game].pieces[val.pos_y] = {};

                        this.games[r.game].pieces[val.pos_y][val.pos_x] = {
                            image: val.image,
                            type: val.type,
                            id: val.id
                        }
                    });
                    this.key += 1;
                },
                setCell(game, y, x, val) {
                    let pieces = this.games[game].pieces;
                    pieces[y] = pieces[y] || {};
                    pieces[y][x] = val;
                },
                getCell(game, y, x) {
                    let result = undefined;
                    let pieces = this.games[game].pieces;
                    if (pieces[y] !== undefined && pieces[y][x] !== undefined)
                        result = pieces[y][x];

                    return result;
                },
                cellHasPiece(game, y, x) {
                    let result = false;
                    let pieces = this.games[game].pieces;
                    if (pieces[y] !== undefined && pieces[y][x] !== undefined)
                        result = pieces[y][x].image !== undefined;

                    return result;
                },
                showMoves(game, data) {
                    let pieces = this.games[game].pieces;
                    data = Object.values(data);
                    data.forEach(val => {
                        if (pieces[val.y] !== undefined && pieces[val.y][val.x] !== undefined)
                            pieces[val.y][val.x].back_kill = true;
                        else
                            this.setCell(game, val.y, val.x, {back_move: true});
                    });
                },
                showSelfCell(game, element) {
                    this.games[game].pieces[this.currentDraggable.y][this.currentDraggable.x].back_self = true;
                },
                hasBack(game, y, x, back) {
                    let result = false;

                    let cell = this.getCell(game, y, x);
                    if (cell !== undefined && cell[`back_${back}`] !== undefined)
                        result = true;

                    return result;
                },
                bindDraggale() {
                    for (let game in this.games) {
                        let pieces = this.games[game].pieces;
                        for (let pos_y in pieces) {
                            for (let pos_x in pieces[pos_y]) {
                                let val = pieces[pos_y][pos_x];
                                let vue = this;

                                let width = $('.piece_img_container', $(`.cell[data-x="${pos_x}"][data-y="${pos_y}"]`)).width();
                                $('.piece_img_container', $(`.field[data-game="${game}"] .cell[data-x="${pos_x}"][data-y="${pos_y}"]`)).draggable({
                                    cursorAt: {left: width/2, top: width/2},
                                    start: function(event, ui) {
                                        vue.currentDraggable = {
                                            x: pos_x,
                                            y: pos_y
                                        };
                                        $(this).css('z-index', '1000');
                                        if (!vue.initializedWithSocket) {
                                            $.ajax({
                                                url: '/game/moves',
                                                method: 'GET',
                                                data: {
                                                    id: val.id,
                                                    type: val.type,
                                                    _token: "{{ csrf_token() }}"
                                                },
                                                success: response => {
                                                    showMoves(response);
                                                },
                                                error: response => {

                                                },
                                                complete: response => {
                                                    showSelfCell(this);
                                                }
                                            });
                                        } else {
                                            vue.socket.send(JSON.stringify({
                                                'type': 'get_moves',
                                                'id': val.id,
                                                user_id: "{{ Auth::id() }}",
                                                game_id: game
                                            }));
                                        }
                                    },
                                    stop: function(event, ui) {
                                        $(this).css('z-index', '100');
                                        let $cellUnderCursor = $(document.elementsFromPoint(event.pageX, event.pageY - $(document).scrollTop())).filter('.cell').first();
                                        if (!$cellUnderCursor.length) {
                                            $(this).attr('style', '');
                                            ['background_kill', 'background_move', 'background_self'].forEach(val => {
                                                $(`.${val}`).removeClass(val).removeClass(val+"__hover");
                                            });
                                            return;
                                        }

                                        if (!vue.initializedWithSocket) {
                                            $.ajax({
                                                url: '/game/move',
                                                method: 'PATCH',
                                                data: {
                                                    id: val.id,
                                                    x: $cellUnderCursor.data('x'),
                                                    y: $cellUnderCursor.data('y'),
                                                    game_id: game,
                                                    _token: "{{ csrf_token() }}"
                                                },
                                                success: response => {
                                                    if (response.length !== 0)
                                                        $cellUnderCursor.html(this);
                                                },
                                                error: response => {

                                                },
                                                complete: response => {
                                                    $(this).attr('style', '');
                                                    ['background_kill', 'background_move', 'background_self'].forEach(val => {
                                                        $(`.${val}`).removeClass(val).removeClass(val+"__hover");
                                                    });
                                                }
                                            });
                                        } else {
                                            vue.socket.send(JSON.stringify({
                                                type: 'move',
                                                id: val.id,
                                                x: $cellUnderCursor.data('x'),
                                                y: $cellUnderCursor.data('y'),
                                                game_id: game,
                                                user_id: "{{ Auth::id() }}"
                                            }));
                                        }
                                    }
                                });
                            }
                        }
                    }
                }
            }
        };

        Vue.createApp(app).mount('#app');

        $('body').delegate('.field', 'mousemove', function(event) {
            let $cellUnderCursor = $(document.elementsFromPoint(event.pageX, event.pageY - $(document).scrollTop())).filter('.cell').first();
            ['background_move', 'background_kill', 'background_self'].forEach(val => {
                $(`.${val}__hover`).removeClass(`${val}__hover`);
                if ($cellUnderCursor.hasClass(val))
                    $cellUnderCursor.addClass(`${val}__hover`);
            });
        });

        $(window).on('resize', function() {
            let width = $('.piece_img_container').width();
            $( '.piece_img_container' ).draggable( "option", "cursorAt", { left: width/2 } );
        })
    </script>
@endpush

@section('content')
<div class="row justify-content-center no-gutters" id="app">
    <div class="col-md-12 justify-content-between">
        <div class="row no-gutters justify-content-between px-3">
            <div class="col-lg-3 card order-1 order-lg-0">
                <div class="card-header d-flex justify-content-between align-items-center">Игры <button data-toggle="modal" data-target="#create_modal" class="btn btn-outline-success btn-sm">Новая</button></div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto">
                    <nav class="nav nav-pills nav-justified justify-content-center">
                        @foreach ($games as $gameNavItem)
                            <div class="w-100 text-center">
                                <a class="nav-link mb-2 @if($gameNavItem->id == $game->id) active @endif" href="{{ route('chess', ['id'=>$gameNavItem->id]) }}">Игра #{{ $gameNavItem->id }}</a>
                            </div>
                        @endforeach
                    </nav>
                </div>
            </div>
            <div class="col-lg-8 card order-0 order-lg-1 mb-4 mb-lg-0">
                <div class="card-header" @click="fieldLength = 3">Шахматы</div>

                <div class="card-body">
                    <div id="field" class="d-flex justify-content-center">
                        <div class="field" v-for="(val,game_id) in games" :data-game="game_id" key="key">
                            <div v-for="i in fieldLength" :key="key" :class="{'field_row': true, 'border-top': i==1}">
                                <div v-for="j in fieldLength"
                                    :key="key"
                                    :data-x="j"
                                    :data-y="fieldLength - i + 1"
                                    :class="{'cell': true,
                                             'border-left': j==1,
                                             'background_self': hasBack(game_id, fieldLength - i + 1, j, 'self'),
                                             'background_kill': hasBack(game_id, fieldLength - i + 1, j, 'kill'),
                                             'background_move': hasBack(game_id, fieldLength - i + 1, j, 'move')
                                            }">
                                    <div :key="key" class="piece_img_container" v-if="cellHasPiece(game_id, fieldLength - i + 1, j)">
                                        <img :src="games[game_id].pieces[fieldLength - i + 1][j].image">
                                        <div class="piece_img_container__block"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
    @include('create_game_modal')
@endpush
