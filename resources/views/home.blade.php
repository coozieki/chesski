@extends('layouts.app')

@push('css')
    <style>
        canvas {
            margin: 0;
        }

        .field .cell {
            width: 80px;
            height: 80px;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
            cursor: pointer;
        }

        .field .row:nth-child(even) .cell:nth-child(odd) {
            background-color: #00000033
        }

        .field .row:nth-child(odd) .cell:nth-child(even) {
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

@push('js')
    <script>
        var socket;
        var game_id = Number("{{ $game?->id ?: 0 }}");
        var initializedWithSocket = false;
        var currentDraggable;

        $(document).ready(function() {
            socket = new WebSocket("ws://127.0.0.1:8090");

            socket.onopen = () => {
                console.log('Соединение установлено');
                initializedWithSocket = true;
                socket.send(JSON.stringify({
                    type: 'init',
                    game_id: game_id
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
            for(let j=0; j<8; j++) {
                $tr = $(`<div class="row${j == 0 ? ' border-top' : ''}"></div>`);
                for(let i=0; i<8; i++) {
                    $tr.append($(`
                        <div class="cell${i == 0 ? ' border-left' : ''}" data-x="${i+1}" data-y="${8-j}">
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

                $('.piece_img_container', $cell).draggable({
                    cursorAt: {left: 40, top: 40},
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
                        let $cellUnderCursor = $(document.elementsFromPoint(event.pageX, event.pageY)).filter('.cell').first();
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
            let $cellUnderCursor = $(document.elementsFromPoint(event.pageX, event.pageY)).filter('.cell').first();
            ['background_move', 'background_kill', 'background_self'].forEach(val => {
                $(`.${val}__hover`).removeClass(`${val}__hover`);
                if ($cellUnderCursor.hasClass(val))
                    $cellUnderCursor.addClass(`${val}__hover`);
            });
        });
    </script>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12 d-flex justify-content-between">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">Игры <a href="{{ route('chess') }}" class="btn btn-outline-success btn-sm">Новая</a></div>
                <div class="card-body" style="width: 250px; height: 600px; overflow-y: auto">
                    <nav class="nav nav-pills nav-justified justify-content-center">
                        @foreach ($games as $gameNavItem)
                            <div class="w-100 text-center">
                                <a class="nav-link mb-2 @if($gameNavItem->id == $game->id) active @endif" href="{{ route('chess', ['id'=>$gameNavItem->id]) }}">Игра #{{ $gameNavItem->id }}</a>
                            </div>
                        @endforeach
                    </nav>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Шахматы</div>

                <div class="card-body">
                    <div id="field" class="d-flex justify-content-center" style="min-width: 640px">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
