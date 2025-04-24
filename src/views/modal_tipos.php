<!-- Modal -->
<div class="modal fade" id="tipoModal" tabindex="-1" aria-labelledby="mcpModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tipoModalLabel">Adicionar Tipo de Produto</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="tipoForm">
          <input type="hidden" id="action_tipo" name="action_tipo">
          <input type="hidden" id="id" name="id">
          <div class="form-group">
            <label for="nome">Nome do Produto</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
          </div>
          <button type="submit" class="btn btn-primary btn-acao">Salvar</button>
        </form>
      </div>
    </div>
  </div>
</div>