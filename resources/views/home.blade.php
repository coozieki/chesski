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
        function initGame(r) {
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
                        $(this).css('z-index', '1000');
                        $.ajax({
                            url: '/game/moves',
                            method: 'GET',
                            data: {
                                id: val.id,
                                type: val.type,
                                _token: "{{ csrf_token() }}"
                            },
                            success: response => {
                                response = Object.values(response);
                                response.forEach(val => {
                                    $cellToMove = $(`.cell[data-x="${val.x}"][data-y="${val.y}"]`);

                                    if ($cellToMove.find('.piece_img_container').length>0) {
                                        $(`.cell[data-x="${val.x}"][data-y="${val.y}"]`).addClass('background_kill');
                                    } else {
                                        $(`.cell[data-x="${val.x}"][data-y="${val.y}"]`).addClass('background_move');
                                    }

                                });
                            },
                            error: response => {

                            },
                            complete: response => {
                                $(this).parents('.cell').first().addClass('background_self');
                            }
                        });
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

                        $.ajax({
                            url: '/game/move',
                            method: 'PATCH',
                            data: {
                                id: val.id,
                                type: val.type,
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
                    }
                });
            });
        }

        fetch('/game/start')
            .then(r => r.json())
            .then(r => initGame(r));

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
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Шахматы</div>

                <div class="card-body">
                    <div id="field" class="d-flex justify-content-center">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
