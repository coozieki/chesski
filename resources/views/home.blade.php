@extends('layouts.app')

@push('css')
    <style>
        canvas {
            margin: 0;
        }

        .table td {
            height: 70px;
            width: 70px;
        }

        .table-bordered td,
        .table-bordered th {
            border-color: black;
        }

        .field .cell {
            width: 50px;
            height: 50px;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
            cursor: pointer;
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
                    stop: function(event, ui) {
                        let $cellUnderCursor = $(document.elementsFromPoint(event.pageX, event.pageY)).filter('.cell').first();
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
                                if (response.length != 0)
                                    $cellUnderCursor.html(this);
                            },
                            error: response => {

                            },
                            complete: response => {
                                $(this).attr('style', '');
                            }
                        });
                    }
                });
            });
        }

        fetch('/game/start')
            .then(r => r.json())
            .then(r => initGame(r));
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
