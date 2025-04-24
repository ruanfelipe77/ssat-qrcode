<!-- Modal -->
<div class="modal fade" id="mcpModal" tabindex="-1" aria-labelledby="mcpModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mcpModalLabel">Adicionar Produto</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="mcpForm">
          <input type="hidden" id="action" name="action">
          <input type="hidden" id="id" name="id">
          <!-- <input type="hidden" id="tipo_id" name="tipo_id"> -->
          <div class="form-group">
            <label for="tipo_id">Tipo:</label>
            <select class="form-control" name="tipo_id" id="tipo_id">
              <?php foreach ($tipos as $tipo) : ?>
                  <option value="<?= $tipo['id'] ?>">
                      <?= $tipo['nome'] ?>
                  </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="serial_number">Número de Série</label>
            <input type="text" class="form-control" id="serial_number" name="serial_number" required>
          </div>
          <div class="form-group">
            <label for="sale_date">Data de Venda</label>
            <input type="date" class="form-control" id="sale_date" name="sale_date" required>
          </div>
          <div class="form-group">
            <label for="destination">Destino</label>
            <input type="text" class="form-control" id="destination" name="destination" required>
          </div>
          <div class="form-group">
            <label for="warranty">Garantia</label>
            <input type="text" class="form-control" id="warranty" name="warranty" required>
          </div>
          <button type="submit" class="btn btn-primary btn-acao">Salvar</button>
        </form>
      </div>
    </div>
  </div>
</div>