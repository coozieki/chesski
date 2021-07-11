<div class="modal fade" id="create_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLongTitle">Создать новую игру</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <form action="{{ route('chess') }}">
                <label for="type">Тип игры:</label>
                <select name="type" id="type" class="form-control mb-3">
                    <option value="ordinary">Обычная 8х8</option>
                    <option value="ordinary">Большая 16х16</option>
                </select>

                <label for="color">Цвет фигур:</label>
                <select name="color" id="color" class="form-control">
                    <option value="0">Белые</option>
                    <option value="1">Черные</option>
                </select>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
            <button type="button" class="btn btn-primary">Создать</button>
        </div>
        </div>
    </div>
</div>
